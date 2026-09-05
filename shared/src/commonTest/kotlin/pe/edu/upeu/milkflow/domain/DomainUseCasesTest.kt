package pe.edu.upeu.milkflow.domain

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFailsWith
import kotlin.test.assertNull
import kotlin.test.assertSame
import kotlin.time.Instant
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerTotalLeche
import pe.edu.upeu.milkflow.domain.usecase.RegistrarEntregaDirecta
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLecheRecogida
import pe.edu.upeu.milkflow.domain.usecase.RegistrarPruebaCalidad

class DomainUseCasesTest {
    private val fecha = Instant.parse("2026-09-01T15:30:00Z")

    @Test
    fun registrarEntregaConProductorActivo() = runTest {
        val productores = FakeProductorRepository(Productor("p-1", "Ana", activo = true))
        val entregas = FakeEntregaRepository()
        val registrar = RegistrarEntregaDirecta(productores, entregas)

        val resultado = registrar(
            id = "e-1",
            productorId = "p-1",
            fechaHora = fecha,
            litros = 18.5,
            usuarioRegistroId = "u-1",
        )

        assertSame(resultado, entregas.guardadas.single())
        assertEquals(EstadoSincronizacion.PENDIENTE, resultado.estadoSincronizacion)
    }

    @Test
    fun rechazarEntregaConProductorInactivo() = runTest {
        val productores = FakeProductorRepository(Productor("p-1", "Ana", activo = false))
        val registrar = RegistrarEntregaDirecta(productores, FakeEntregaRepository())

        assertFailsWith<ProductorInactivoException> {
            registrar("e-1", "p-1", fecha, 18.5, "u-1")
        }
    }

    @Test
    fun rechazarEntregaCuandoProductorNoExiste() = runTest {
        val registrar = RegistrarEntregaDirecta(
            FakeProductorRepository(),
            FakeEntregaRepository(),
        )

        assertFailsWith<ProductorNoEncontradoException> {
            registrar("e-1", "desconocido", fecha, 18.5, "u-1")
        }
    }

    @Test
    fun rechazarLitrosMenoresOIgualesACero() = runTest {
        val productores = FakeProductorRepository(Productor("p-1", "Ana", activo = true))
        val registrar = RegistrarEntregaDirecta(productores, FakeEntregaRepository())

        assertFailsWith<LitrosInvalidosException> {
            registrar("e-1", "p-1", fecha, 0.0, "u-1")
        }
        assertFailsWith<LitrosInvalidosException> {
            registrar("e-2", "p-1", fecha, -1.0, "u-1")
        }
    }

    @Test
    fun registrarEntregaDirectaSinAcopiadorNiSector() = runTest {
        val registrar = RegistrarEntregaDirecta(
            FakeProductorRepository(Productor("p-1", "Ana", activo = true)),
            FakeEntregaRepository(),
        )

        val entrega = registrar("e-1", "p-1", fecha, 25.0, "u-1")

        assertEquals(TipoEntrega.DIRECTA, entrega.tipo)
        assertNull(entrega.acopiadorId)
        assertNull(entrega.sector)
    }

    @Test
    fun registrarEntregaRecogidaConAcopiadorYSector() = runTest {
        val registrar = RegistrarLecheRecogida(
            productorRepository = FakeProductorRepository(
                Productor("p-1", "Ana", activo = true),
            ),
            acopiadorRepository = FakeAcopiadorRepository(Acopiador("a-1", "Luis")),
            entregaRepository = FakeEntregaRepository(),
        )

        val entrega = registrar(
            id = "e-1",
            productorId = "p-1",
            fechaHora = fecha,
            litros = 30.0,
            usuarioRegistroId = "u-1",
            acopiadorId = "a-1",
            sector = "Sector Norte",
        )

        assertEquals(TipoEntrega.RECOGIDA, entrega.tipo)
        assertEquals("a-1", entrega.acopiadorId)
        assertEquals("Sector Norte", entrega.sector)
    }

    @Test
    fun pruebaCalidadDebeEstarAsociadaAEntregaExistente() = runTest {
        val entrega = entregaDirecta("e-1", 20.0)
        val entregas = FakeEntregaRepository(entrega)
        val calidad = FakeCalidadRepository()
        val registrar = RegistrarPruebaCalidad(entregas, calidad)
        val prueba = PruebaCalidad("c-1", "e-1")

        assertSame(prueba, registrar(prueba))
        assertSame(prueba, calidad.pruebas.single())

        assertFailsWith<EntregaNoEncontradaException> {
            registrar(PruebaCalidad("c-2", "e-inexistente"))
        }
    }

    @Test
    fun rechazarPruebaCalidadSinIdentificadorDeEntrega() {
        assertFailsWith<PruebaCalidadInvalidaException> {
            PruebaCalidad("c-1", "")
        }
    }

    @Test
    fun validarRangoDeFechas() {
        val inicio = Instant.parse("2026-09-01T00:00:00Z")
        val fin = Instant.parse("2026-09-02T00:00:00Z")
        val rango = RangoFechas(inicio, fin)

        assertEquals(true, inicio in rango)
        assertEquals(false, fin in rango)
        assertFailsWith<RangoFechasInvalidoException> { RangoFechas(inicio, inicio) }
        assertFailsWith<RangoFechasInvalidoException> { RangoFechas(fin, inicio) }
    }

    @Test
    fun calcularTotalDeLitrosEnRango() = runTest {
        val rango = RangoFechas(
            Instant.parse("2026-09-01T00:00:00Z"),
            Instant.parse("2026-09-02T00:00:00Z"),
        )
        val entregas = FakeEntregaRepository(
            entregaDirecta("e-1", 12.5),
            entregaDirecta("e-2", 30.25),
            entregaDirecta(
                id = "e-fuera",
                litros = 100.0,
                fechaHora = Instant.parse("2026-09-03T00:00:00Z"),
            ),
        )

        assertEquals(42.75, ObtenerTotalLeche(entregas)(rango))
    }

    private fun entregaDirecta(
        id: String,
        litros: Double,
        fechaHora: Instant = fecha,
    ) = Entrega(
        id = id,
        productorId = "p-1",
        fechaHora = fechaHora,
        litros = litros,
        tipo = TipoEntrega.DIRECTA,
        usuarioRegistroId = "u-1",
    )
}

private class FakeProductorRepository(
    vararg productores: Productor,
) : ProductorRepository {
    private val datos = productores.associateBy { it.id }.toMutableMap()

    override suspend fun obtenerPorId(id: String): Productor? = datos[id]

    override suspend fun obtenerTodos(): List<Productor> = datos.values.toList()

    override suspend fun guardar(productor: Productor): Productor =
        productor.also { datos[it.id] = it }
}

private class FakeAcopiadorRepository(
    vararg acopiadores: Acopiador,
) : AcopiadorRepository {
    private val datos = acopiadores.associateBy { it.id }.toMutableMap()

    override suspend fun obtenerPorId(id: String): Acopiador? = datos[id]

    override suspend fun obtenerTodos(): List<Acopiador> = datos.values.toList()

    override suspend fun guardar(acopiador: Acopiador): Acopiador =
        acopiador.also { datos[it.id] = it }
}

private class FakeEntregaRepository(
    vararg entregasIniciales: Entrega,
) : EntregaRepository {
    val guardadas = entregasIniciales.toMutableList()

    override suspend fun obtenerPorId(id: String): Entrega? = guardadas.find { it.id == id }

    override suspend fun obtenerTodas(): List<Entrega> = guardadas.toList()

    override suspend fun guardar(entrega: Entrega): Entrega =
        entrega.also { guardadas += it }

    override suspend fun obtenerPorProductor(
        productorId: String,
        rango: RangoFechas?,
    ): List<Entrega> = guardadas.filter {
        it.productorId == productorId && (rango == null || it.fechaHora in rango)
    }

    override suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega> =
        guardadas.filter { it.fechaHora in rango }
}

private class FakeCalidadRepository : CalidadRepository {
    val pruebas = mutableListOf<PruebaCalidad>()
    val problemas = mutableListOf<ProblemaLeche>()

    override suspend fun obtenerPruebaPorId(id: String): PruebaCalidad? =
        pruebas.find { it.id == id }

    override suspend fun obtenerPruebaPorEntrega(entregaId: String): PruebaCalidad? =
        pruebas.find { it.entregaId == entregaId }

    override suspend fun obtenerProblemasPorEntrega(entregaId: String): List<ProblemaLeche> =
        problemas.filter { it.entregaId == entregaId }

    override suspend fun guardarPrueba(prueba: PruebaCalidad): PruebaCalidad =
        prueba.also { pruebas += it }

    override suspend fun guardarProblema(problema: ProblemaLeche): ProblemaLeche =
        problema.also { problemas += it }
}
