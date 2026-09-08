package pe.edu.upeu.milkflow.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.test.assertFailsWith
import kotlin.time.Instant
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.repository.SqlDelightAcopiadorRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightAuditoriaRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightCalidadRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightEntregaRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightProductorRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightSincronizacionRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightUsuarioRepository
import pe.edu.upeu.milkflow.domain.ProductorInactivoException
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerTotalLeche

class DataRepositoriesTest {
    private lateinit var driver: JdbcSqliteDriver
    private lateinit var database: MilkFlowDatabase
    private lateinit var productores: SqlDelightProductorRepository
    private lateinit var acopiadores: SqlDelightAcopiadorRepository
    private lateinit var entregas: SqlDelightEntregaRepository
    private lateinit var calidad: SqlDelightCalidadRepository
    private lateinit var sincronizacion: SqlDelightSincronizacionRepository
    private lateinit var auditoria: SqlDelightAuditoriaRepository

    @BeforeTest
    fun preparar() {
        driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
        MilkFlowDatabase.Schema.create(driver)
        database = MilkFlowDatabase(driver)
        productores = SqlDelightProductorRepository(database)
        acopiadores = SqlDelightAcopiadorRepository(database)
        entregas = SqlDelightEntregaRepository(database)
        calidad = SqlDelightCalidadRepository(database)
        sincronizacion = SqlDelightSincronizacionRepository(database)
        auditoria = SqlDelightAuditoriaRepository(database)
    }

    @AfterTest
    fun cerrar() {
        driver.close()
    }

    @Test
    fun guardaYRecuperaProductor() = runTest {
        val productor = productorActivo()

        productores.guardar(productor)

        assertEquals(productor, productores.obtenerPorId(productor.id))
        assertEquals(listOf(productor), productores.obtenerTodos())
    }

    @Test
    fun actualizaProductorSinEliminarlo() = runTest {
        productores.guardar(productorActivo())

        productores.guardar(Productor("prod-1", "Rosa Quispe", activo = false))

        val actualizado = assertNotNull(productores.obtenerPorId("prod-1"))
        assertEquals("Rosa Quispe", actualizado.nombre)
        assertFalse(actualizado.activo)
    }

    @Test
    fun guardaYRecuperaAcopiador() = runTest {
        val acopiador = acopiador()

        acopiadores.guardar(acopiador)

        assertEquals(acopiador, acopiadores.obtenerPorId(acopiador.id))
        assertEquals(listOf(acopiador), acopiadores.obtenerTodos())
    }

    @Test
    fun guardaEntregaDirecta() = runTest {
        productores.guardar(productorActivo())
        val entrega = entregaDirecta()

        entregas.guardar(entrega)

        assertEquals(entrega, entregas.obtenerPorId(entrega.id))
        assertEquals(listOf(entrega), entregas.obtenerTodas())
        assertNull(entregas.obtenerPorId(entrega.id)?.acopiadorId)
    }

    @Test
    fun guardaEntregaRecogida() = runTest {
        productores.guardar(productorActivo())
        acopiadores.guardar(acopiador())
        val entrega = entregaRecogida()

        entregas.guardar(entrega)

        assertEquals(entrega, entregas.obtenerPorId(entrega.id))
    }

    @Test
    fun rechazaEntregaDeProductorInactivo() = runTest {
        productores.guardar(productorActivo().copy(activo = false))

        assertFailsWith<ProductorInactivoException> {
            entregas.guardar(entregaDirecta())
        }
    }

    @Test
    fun recuperaEntregasPorProductor() = runTest {
        productores.guardar(productorActivo())
        entregas.guardar(entregaDirecta())
        entregas.guardar(entregaDirecta(id = "ent-2", litros = 8.5))

        val recuperadas = entregas.obtenerPorProductor("prod-1")

        assertEquals(listOf("ent-1", "ent-2"), recuperadas.map(Entrega::id))
    }

    @Test
    fun obtieneTotalDeLitros() = runTest {
        productores.guardar(productorActivo())
        entregas.guardar(entregaDirecta(litros = 12.25))
        entregas.guardar(entregaDirecta(id = "ent-2", litros = 7.75))
        val rango = RangoFechas(
            inicio = Instant.parse("2026-08-31T00:00:00Z"),
            finExclusivo = Instant.parse("2026-09-02T00:00:00Z"),
        )

        assertEquals(20.0, ObtenerTotalLeche(entregas)(rango))
    }

    @Test
    fun guardaPruebaVinculadaAEntrega() = runTest {
        guardarEntregaBase()
        val prueba = PruebaCalidad("prueba-1", "ent-1", Instant.parse("2026-09-01T10:00:00Z"))

        calidad.guardarPrueba(prueba)

        assertEquals(prueba, calidad.obtenerPruebaPorId(prueba.id))
        assertEquals(prueba, calidad.obtenerPruebaPorEntrega(prueba.entregaId))
    }

    @Test
    fun registrarProblemaMantieneEntregaOriginal() = runTest {
        guardarEntregaBase()
        val problema = ProblemaLeche("problema-1", "ent-1", "Observación visual", Instant.parse("2026-09-01T10:00:00Z"))

        calidad.guardarProblema(problema)

        assertNotNull(entregas.obtenerPorId("ent-1"))
        assertEquals(listOf(problema), calidad.obtenerProblemasPorEntrega("ent-1"))
    }

    @Test
    fun guardaYRecuperaPruebaPorRango() = runTest {
        productores.guardar(productorActivo())
        entregas.guardar(entregaDirecta())
        
        val fecha = Instant.parse("2026-09-07T15:00:00Z")
        val prueba = PruebaCalidad("q1", "ent-1", fecha)
        calidad.guardarPrueba(prueba)
        
        val rango = RangoFechas(
            inicio = Instant.parse("2026-09-07T00:00:00Z"),
            finExclusivo = Instant.parse("2026-09-08T00:00:00Z")
        )
        
        val resultados = calidad.obtenerPruebasPorRango(rango)
        assertEquals(1, resultados.size)
        assertEquals(fecha, resultados.first().fechaHora)
        assertEquals(
            1788793200000L,
            database.milkFlowQueries.obtenerPruebaPorId("q1")
                .executeAsOne()
                .fecha_hora_epoch_millis,
        )
        assertEquals(prueba, calidad.obtenerPruebaPorId("q1"))
        assertEquals(listOf(prueba), calidad.obtenerTodasLasPruebas())

        val rangoFueraDelDia = RangoFechas(
            inicio = Instant.parse("2026-09-08T00:00:00Z"),
            finExclusivo = Instant.parse("2026-09-09T00:00:00Z"),
        )
        assertTrue(calidad.obtenerPruebasPorRango(rangoFueraDelDia).isEmpty())
    }

    @Test
    fun obtieneRegistrosPendientesSinDuplicarlos() = runTest {
        guardarEntregaBase()
        entregas.guardar(entregaDirecta())

        val pendientes = sincronizacion.obtenerPendientes()

        assertEquals(1, pendientes.size)
        assertEquals("ent-1", pendientes.single().registroId)
        assertEquals(EstadoSincronizacion.PENDIENTE, pendientes.single().estado)
        assertEquals(pendientes, sincronizacion.observarPendientes().first())
    }

    @Test
    fun cambiaEstadoDeSincronizacion() = runTest {
        guardarEntregaBase()

        val actualizado = sincronizacion.actualizarEstado(
            tipoRegistro = SqlDelightEntregaRepository.TIPO_ENTREGA,
            registroId = "ent-1",
            estado = EstadoSincronizacion.ENVIADO,
        )

        assertTrue(actualizado)
        assertEquals(
            EstadoSincronizacion.ENVIADO,
            sincronizacion.obtenerEstado(SqlDelightEntregaRepository.TIPO_ENTREGA, "ent-1"),
        )
        assertEquals(EstadoSincronizacion.ENVIADO, entregas.obtenerPorId("ent-1")?.estadoSincronizacion)
        assertTrue(sincronizacion.obtenerPendientes().isEmpty())
    }

    @Test
    fun guardaYRecuperaAuditoriaSinSobrescribir() = runTest {
        val original = RegistroAuditoria(
            id = "audit-1",
            usuarioId = "user-1",
            fechaHora = Instant.parse("2026-09-01T10:15:00Z"),
            registroAfectadoId = "ent-1",
            accion = "CREAR",
        )
        auditoria.guardar(original)
        auditoria.guardar(original.copy(accion = "ALTERAR"))

        assertEquals(original, auditoria.obtenerPorId(original.id))
    }

    @Test
    fun persisteUsuarioYDelegaValidacionDeCredencial() = runTest {
        val usuarios = SqlDelightUsuarioRepository(database) { id, clave ->
            id == "user-1" && clave == "clave-de-prueba"
        }
        val usuario = Usuario(
            id = "user-1",
            nombreUsuario = "admin",
            nombre = "Administradora",
            rol = RolUsuario.ADMINISTRADORA,
            activo = true,
        )
        usuarios.guardar(usuario, "clave-local")

        assertEquals(usuario, usuarios.obtenerPorId(usuario.id))
        assertEquals(listOf(usuario), usuarios.observarTodos().first())
        
        // Autentica con clave local
        assertEquals(usuario, usuarios.autenticar("admin", "clave-local"))
        // Autentica con validador externo (fallback)
        assertEquals(usuario, usuarios.autenticar("admin", "clave-de-prueba"))
        
        assertNull(usuarios.autenticar("admin", "incorrecta"))
    }

    private suspend fun guardarEntregaBase() {
        productores.guardar(productorActivo())
        entregas.guardar(entregaDirecta())
    }

    private fun productorActivo() = Productor("prod-1", "Rosa", activo = true)

    private fun acopiador() = Acopiador("acop-1", "Luis")

    private fun entregaDirecta(
        id: String = "ent-1",
        litros: Double = 10.0,
    ) = Entrega(
        id = id,
        productorId = "prod-1",
        fechaHora = Instant.parse("2026-09-01T10:00:00Z"),
        litros = litros,
        tipo = TipoEntrega.DIRECTA,
        usuarioRegistroId = "user-1",
    )

    private fun entregaRecogida() = Entrega(
        id = "ent-rec-1",
        productorId = "prod-1",
        fechaHora = Instant.parse("2026-09-01T11:00:00Z"),
        litros = 15.5,
        tipo = TipoEntrega.RECOGIDA,
        usuarioRegistroId = "user-1",
        acopiadorId = "acop-1",
        sector = "Sector Norte",
    )
}
