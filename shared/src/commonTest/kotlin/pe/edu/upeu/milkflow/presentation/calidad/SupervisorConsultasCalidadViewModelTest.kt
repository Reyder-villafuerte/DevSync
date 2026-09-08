package pe.edu.upeu.milkflow.presentation.calidad

import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue
import kotlin.time.Instant
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import kotlinx.datetime.TimeZone
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

@OptIn(ExperimentalCoroutinesApi::class)
class SupervisorConsultasCalidadViewModelTest {
    private val dispatcher = StandardTestDispatcher()
    private val zonaLima = TimeZone.of("America/Lima")
    private val ahora = Instant.parse("2026-09-08T02:00:00Z")

    @BeforeTest
    fun setUp() {
        Dispatchers.setMain(dispatcher)
    }

    @AfterTest
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `inspecciones de hoy usa limites del dia local`() = runTest(dispatcher) {
        val pruebaDelDia = PruebaCalidad(
            id = "q-hoy",
            entregaId = "e-1",
            fechaHora = Instant.parse("2026-09-07T19:31:00Z"),
        )
        val pruebaFueraDelDia = PruebaCalidad(
            id = "q-manana",
            entregaId = "e-2",
            fechaHora = Instant.parse("2026-09-08T05:00:00Z"),
        )
        val calidad = FakeSupervisorCalidadRepository(pruebaDelDia, pruebaFueraDelDia)
        val viewModel = crearViewModel(calidad)

        advanceUntilIdle()

        assertEquals(
            RangoFechas(
                inicio = Instant.parse("2026-09-07T05:00:00Z"),
                finExclusivo = Instant.parse("2026-09-08T05:00:00Z"),
            ),
            calidad.ultimoRangoConsultado,
        )
        val success = assertIs<InspeccionesHoyState.Success>(viewModel.uiState.value.inspeccionesHoy)
        assertEquals(listOf("q-hoy"), success.inspecciones.map { it.prueba.id })
    }

    @Test
    fun `refresh al entrar carga una prueba creada despues del init`() = runTest(dispatcher) {
        val calidad = FakeSupervisorCalidadRepository()
        val viewModel = crearViewModel(calidad)
        advanceUntilIdle()
        assertIs<InspeccionesHoyState.Empty>(viewModel.uiState.value.inspeccionesHoy)

        calidad.pruebas += PruebaCalidad(
            id = "q-nueva",
            entregaId = "e-1",
            fechaHora = Instant.parse("2026-09-07T19:31:00Z"),
        )
        viewModel.onEvent(SupervisorConsultasUiEvent.RefreshInspecciones)
        advanceUntilIdle()

        val success = assertIs<InspeccionesHoyState.Success>(viewModel.uiState.value.inspeccionesHoy)
        assertEquals("q-nueva", success.inspecciones.single().prueba.id)
        assertTrue(calidad.consultasDeRango >= 2)
    }

    private fun crearViewModel(
        calidad: FakeSupervisorCalidadRepository,
    ) = SupervisorConsultasCalidadViewModel(
        calidadRepository = calidad,
        entregaRepository = FakeSupervisorEntregaRepository(),
        ahora = { ahora },
        zonaHoraria = { zonaLima },
    )
}

private class FakeSupervisorCalidadRepository(
    vararg initial: PruebaCalidad,
) : CalidadRepository {
    val pruebas = initial.toMutableList()
    var ultimoRangoConsultado: RangoFechas? = null
    var consultasDeRango: Int = 0

    override suspend fun obtenerPruebaPorId(id: String) = pruebas.find { it.id == id }
    override suspend fun obtenerPruebaPorEntrega(entregaId: String) =
        pruebas.find { it.entregaId == entregaId }

    override suspend fun obtenerProblemasPorEntrega(entregaId: String) = emptyList<ProblemaLeche>()

    override suspend fun obtenerPruebasPorRango(rango: RangoFechas): List<PruebaCalidad> {
        ultimoRangoConsultado = rango
        consultasDeRango += 1
        return pruebas.filter { it.fechaHora in rango }
    }

    override suspend fun obtenerProblemasPorRango(rango: RangoFechas) = emptyList<ProblemaLeche>()
    override suspend fun obtenerTodasLasPruebas() = pruebas.toList()
    override suspend fun obtenerTodosLosProblemas() = emptyList<ProblemaLeche>()
    override suspend fun guardarPrueba(prueba: PruebaCalidad) = prueba.also(pruebas::add)
    override suspend fun guardarProblema(problema: ProblemaLeche) = problema
}

private class FakeSupervisorEntregaRepository : EntregaRepository {
    override suspend fun obtenerPorId(id: String): Entrega? = null
    override suspend fun obtenerTodas() = emptyList<Entrega>()
    override suspend fun guardar(entrega: Entrega) = entrega
    override suspend fun obtenerPorProductor(productorId: String, rango: RangoFechas?) = emptyList<Entrega>()
    override suspend fun obtenerPorRango(rango: RangoFechas) = emptyList<Entrega>()
}
