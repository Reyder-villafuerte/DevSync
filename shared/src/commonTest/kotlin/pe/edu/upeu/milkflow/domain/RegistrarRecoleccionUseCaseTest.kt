package pe.edu.upeu.milkflow.domain

import kotlinx.coroutines.test.runTest
import kotlin.time.Instant
import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.EstadoProductor
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.usecase.RegistrarRecoleccionUseCase
import pe.edu.upeu.milkflow.domain.vo.Dni
import pe.edu.upeu.milkflow.domain.vo.Litros
import pe.edu.upeu.milkflow.fakes.GeneradorIdSecuencial
import pe.edu.upeu.milkflow.fakes.JornadaRepositoryFake
import pe.edu.upeu.milkflow.fakes.ProductorRepositoryFake
import pe.edu.upeu.milkflow.fakes.RecoleccionRepositoryFake
import pe.edu.upeu.milkflow.fakes.RelojFijo
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class RegistrarRecoleccionUseCaseTest {

    private val reloj = RelojFijo()
    private val productor = Productor(
        id = "p1", codigoPadron = "P-0001", nombres = "Julio", apellidos = "Cutipa",
        dni = Dni.confiar("70000003"), zonaId = "z1", telefono = null,
        estado = EstadoProductor.ACTIVO, fechaIngreso = LocalDate(2024, 1, 1),
        updatedAt = reloj.ahora(), version = 3,
    )
    private val jornada = JornadaRuta(
        id = "j1", acopiadorId = "ac1", rutaId = "r1", dispositivoId = "d1",
        fecha = LocalDate(2026, 9, 9), horaInicio = Instant.parse("2026-09-09T05:00:00Z"),
        horaCierre = null, litrosDeclarados = Litros.CERO, estado = EstadoJornada.EN_CURSO,
        updatedAt = reloj.ahora(), version = 1,
    )

    private fun caso(
        jornadas: JornadaRepositoryFake = JornadaRepositoryFake(jornada),
        productores: ProductorRepositoryFake = ProductorRepositoryFake(listOf(productor)),
        recolecciones: RecoleccionRepositoryFake = RecoleccionRepositoryFake(),
    ) = Triple(
        jornadas, recolecciones,
        RegistrarRecoleccionUseCase(jornadas, productores, recolecciones, GeneradorIdSecuencial(), reloj),
    )

    @Test
    fun registra_y_encola_sin_red() = runTest {
        val (_, recolecciones, uc) = caso()
        val r = uc(RegistrarRecoleccionUseCase.Entrada(acopiadorId = "ac1", productorId = "p1", litros = 12.5))

        assertIs<Resultado.Exito<*>>(r)
        assertEquals(1, recolecciones.registradas.size)
        assertEquals(12.5, recolecciones.registradas.first().litros.valor)
        // la escritura de negocio dejó su operación en el outbox (misma transacción)
        assertTrue(recolecciones.outbox.contains("registros_acopio" to "id-1"))
    }

    @Test
    fun rechaza_si_no_hay_jornada_abierta() = runTest {
        val (_, _, uc) = caso(jornadas = JornadaRepositoryFake(activa = null))
        val r = uc(RegistrarRecoleccionUseCase.Entrada("ac1", "p1", 10.0))
        assertIs<Resultado.Fallo>(r)
    }

    @Test
    fun rechaza_productor_expulsado() = runTest {
        val expulsado = productor.copy(estado = EstadoProductor.EXPULSADO)
        val (_, _, uc) = caso(productores = ProductorRepositoryFake(listOf(expulsado)))
        val r = uc(RegistrarRecoleccionUseCase.Entrada("ac1", "p1", 10.0))
        assertIs<Resultado.Fallo>(r)
    }

    @Test
    fun rechaza_litros_no_positivos() = runTest {
        val (_, _, uc) = caso()
        assertIs<Resultado.Fallo>(uc(RegistrarRecoleccionUseCase.Entrada("ac1", "p1", 0.0)))
    }

    @Test
    fun rechaza_doble_entrega_del_mismo_productor() = runTest {
        val (_, _, uc) = caso()
        assertIs<Resultado.Exito<*>>(uc(RegistrarRecoleccionUseCase.Entrada("ac1", "p1", 8.0)))
        assertIs<Resultado.Fallo>(uc(RegistrarRecoleccionUseCase.Entrada("ac1", "p1", 9.0)))
    }
}
