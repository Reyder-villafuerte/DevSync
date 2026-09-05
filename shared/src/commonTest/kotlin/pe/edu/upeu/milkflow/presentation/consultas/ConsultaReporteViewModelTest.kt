package pe.edu.upeu.milkflow.presentation.consultas

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
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.ResumenProductor
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasProductor
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteMensual
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteSemanal
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenProductor
import pe.edu.upeu.milkflow.domain.usecase.ObtenerTotalLeche
import pe.edu.upeu.milkflow.presentation.reportes.ReporteContentState
import pe.edu.upeu.milkflow.presentation.reportes.ReportePeriodo
import pe.edu.upeu.milkflow.presentation.reportes.ReporteUiEvent
import pe.edu.upeu.milkflow.presentation.reportes.ReporteViewModel

@OptIn(ExperimentalCoroutinesApi::class)
class ConsultaReporteViewModelTest {
    private val dispatcher = StandardTestDispatcher()
    private val fechaReferencia = LocalDate(2026, 9, 1)

    @BeforeTest
    fun setUp() { Dispatchers.setMain(dispatcher) }

    @AfterTest
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun consultaEntregasDeUnProductor() = runTest(dispatcher) {
        val fixture = fixture()
        val viewModel = ConsultaViewModel(
            fixture.productores,
            ObtenerEntregasProductor(fixture.productores, fixture.entregas),
            zonaHoraria = { TimeZone.UTC },
            hoy = { fechaReferencia },
        )
        advanceUntilIdle()
        viewModel.onEvent(ConsultaUiEvent.SelectProductor("p-1"))
        viewModel.onEvent(ConsultaUiEvent.FechaInicioChanged("2026-09-01"))
        viewModel.onEvent(ConsultaUiEvent.FechaFinChanged("2026-09-02"))
        viewModel.onEvent(ConsultaUiEvent.Buscar)
        advanceUntilIdle()

        val result = assertIs<ConsultaContentState.Success>(viewModel.uiState.value.content)
        assertEquals(listOf("e-1"), result.entregas.map { it.id })
    }

    @Test
    fun consultaNoMezclaProductores() = runTest(dispatcher) {
        val fixture = fixture()
        val viewModel = ConsultaViewModel(
            fixture.productores,
            ObtenerEntregasProductor(fixture.productores, fixture.entregas),
            zonaHoraria = { TimeZone.UTC }, hoy = { fechaReferencia },
        )
        advanceUntilIdle()
        viewModel.onEvent(ConsultaUiEvent.SelectProductor("p-2"))
        viewModel.onEvent(ConsultaUiEvent.Buscar)
        advanceUntilIdle()

        val result = assertIs<ConsultaContentState.Success>(viewModel.uiState.value.content)
        assertEquals(listOf("e-3"), result.entregas.map { it.id })
    }

    @Test
    fun consultaValidaRangoDeFechas() {
        val state = ConsultaUiState(
            productores = listOf(Productor("p-1", "Ana", true)),
            fechaInicio = "2026-09-01",
            fechaFin = "2026-09-01",
            content = ConsultaContentState.Empty,
        )
        assertTrue(state.rangoValido)
    }

    @Test
    fun rechazaRangoInvertido() = runTest(dispatcher) {
        val fixture = fixture()
        val viewModel = ConsultaViewModel(
            fixture.productores,
            ObtenerEntregasProductor(fixture.productores, fixture.entregas),
            zonaHoraria = { TimeZone.UTC }, hoy = { fechaReferencia },
        )
        advanceUntilIdle()
        viewModel.onEvent(ConsultaUiEvent.SelectProductor("p-1"))
        viewModel.onEvent(ConsultaUiEvent.FechaInicioChanged("2026-09-03"))
        viewModel.onEvent(ConsultaUiEvent.FechaFinChanged("2026-09-01"))
        viewModel.onEvent(ConsultaUiEvent.Buscar)

        val error = assertIs<ConsultaContentState.Error>(viewModel.uiState.value.content)
        assertTrue(error.message.contains("posterior"))
    }

    @Test
    fun consultaMuestraEstadoVacio() = runTest(dispatcher) {
        val fixture = fixture(entregas = emptyList())
        val viewModel = ConsultaViewModel(
            fixture.productores,
            ObtenerEntregasProductor(fixture.productores, fixture.entregas),
            zonaHoraria = { TimeZone.UTC }, hoy = { fechaReferencia },
        )
        advanceUntilIdle()
        viewModel.onEvent(ConsultaUiEvent.SelectProductor("p-1"))
        viewModel.onEvent(ConsultaUiEvent.Buscar)
        advanceUntilIdle()

        assertEquals(ConsultaContentState.Empty, viewModel.uiState.value.content)
    }

    @Test
    fun resumenSemanal() = runTest(dispatcher) {
        val fixture = fixture()
        val viewModel = ResumenViewModel(
            fixture.productores,
            ObtenerResumenProductor(fixture.productores, fixture.entregas),
            zonaHoraria = { TimeZone.UTC }, hoy = { fechaReferencia },
        )
        advanceUntilIdle()
        viewModel.onEvent(ResumenUiEvent.SelectProductor("p-1"))
        viewModel.onEvent(ResumenUiEvent.FechaChanged("2026-09-01"))
        viewModel.onEvent(ResumenUiEvent.PeriodoChanged(ResumenPeriodo.SEMANAL))
        viewModel.onEvent(ResumenUiEvent.Consultar)
        advanceUntilIdle()

        val result = assertIs<ResumenContentState.Success>(viewModel.uiState.value.content)
        assertEquals(30.0, result.resumen.totalLitros)
        assertEquals(2, result.resumen.cantidadEntregas)
    }

    @Test
    fun resumenMensual() = runTest(dispatcher) {
        val fixture = fixture()
        val viewModel = ResumenViewModel(
            fixture.productores,
            ObtenerResumenProductor(fixture.productores, fixture.entregas),
            zonaHoraria = { TimeZone.UTC }, hoy = { fechaReferencia },
        )
        advanceUntilIdle()
        viewModel.onEvent(ResumenUiEvent.SelectProductor("p-1"))
        viewModel.onEvent(ResumenUiEvent.FechaChanged("2026-09-15"))
        viewModel.onEvent(ResumenUiEvent.PeriodoChanged(ResumenPeriodo.MENSUAL))
        viewModel.onEvent(ResumenUiEvent.Consultar)
        advanceUntilIdle()

        val result = assertIs<ResumenContentState.Success>(viewModel.uiState.value.content)
        assertEquals(30.0, result.resumen.totalLitros)
        assertEquals(2, result.resumen.cantidadEntregas)
    }

    @Test
    fun reporteDiario() = runTest(dispatcher) {
        val viewModel = reporteViewModel()
        advanceUntilIdle()
        viewModel.onEvent(ReporteUiEvent.FechaChanged("2026-09-01"))
        viewModel.onEvent(ReporteUiEvent.PeriodoChanged(ReportePeriodo.DIARIO))
        viewModel.onEvent(ReporteUiEvent.Consultar)
        advanceUntilIdle()

        val result = assertIs<ReporteContentState.Success>(viewModel.uiState.value.content)
        assertEquals(60.0, result.reporte.totalLitros)
        assertEquals(2, result.reporte.cantidadEntregas)
    }

    @Test
    fun reporteSemanal() = runTest(dispatcher) {
        val viewModel = reporteViewModel()
        advanceUntilIdle()
        viewModel.onEvent(ReporteUiEvent.FechaChanged("2026-09-01"))
        viewModel.onEvent(ReporteUiEvent.PeriodoChanged(ReportePeriodo.SEMANAL))
        viewModel.onEvent(ReporteUiEvent.Consultar)
        advanceUntilIdle()

        val result = assertIs<ReporteContentState.Success>(viewModel.uiState.value.content)
        assertEquals(80.0, result.reporte.totalLitros)
        assertEquals(3, result.reporte.cantidadEntregas)
    }

    @Test
    fun reporteMensual() = runTest(dispatcher) {
        val viewModel = reporteViewModel()
        advanceUntilIdle()
        viewModel.onEvent(ReporteUiEvent.FechaChanged("2026-09-15"))
        viewModel.onEvent(ReporteUiEvent.PeriodoChanged(ReportePeriodo.MENSUAL))
        viewModel.onEvent(ReporteUiEvent.Consultar)
        advanceUntilIdle()

        val result = assertIs<ReporteContentState.Success>(viewModel.uiState.value.content)
        assertEquals(80.0, result.reporte.totalLitros)
        assertEquals(3, result.reporte.cantidadEntregas)
    }

    @Test
    fun reporteMuestraEstadoVacio() = runTest(dispatcher) {
        val fixture = fixture(entregas = emptyList())
        val viewModel = ReporteViewModel(
            ObtenerReporteDiario(fixture.entregas),
            ObtenerReporteSemanal(fixture.entregas),
            ObtenerReporteMensual(fixture.entregas),
            ObtenerTotalLeche(fixture.entregas),
            zonaHoraria = { TimeZone.UTC }, hoy = { fechaReferencia },
        )
        advanceUntilIdle()

        assertEquals(ReporteContentState.Empty, viewModel.uiState.value.content)
    }

    private fun reporteViewModel(): ReporteViewModel {
        val fixture = fixture()
        return ReporteViewModel(
            ObtenerReporteDiario(fixture.entregas),
            ObtenerReporteSemanal(fixture.entregas),
            ObtenerReporteMensual(fixture.entregas),
            ObtenerTotalLeche(fixture.entregas),
            zonaHoraria = { TimeZone.UTC }, hoy = { fechaReferencia },
        )
    }

    private fun fixture(entregas: List<Entrega> = listOf(entrega("e-1", "p-1", 10.0, "2026-09-01T10:00:00Z"), entrega("e-2", "p-1", 20.0, "2026-09-05T10:00:00Z"), entrega("e-3", "p-2", 50.0, "2026-09-01T11:00:00Z"))): ConsultaFixture =
        ConsultaFixture(
            productores = FakeConsultaProductorRepository(
                Productor("p-1", "Ana", true),
                Productor("p-2", "Luis", true),
            ),
            entregas = FakeConsultaEntregaRepository(entregas),
        )

    private fun entrega(id: String, productorId: String, litros: Double, fecha: String) = Entrega(
        id = id,
        productorId = productorId,
        fechaHora = Instant.parse(fecha),
        litros = litros,
        tipo = TipoEntrega.DIRECTA,
        usuarioRegistroId = "u-1",
        estadoSincronizacion = EstadoSincronizacion.PENDIENTE,
    )
}

private data class ConsultaFixture(
    val productores: FakeConsultaProductorRepository,
    val entregas: FakeConsultaEntregaRepository,
)

private class FakeConsultaProductorRepository(vararg initial: Productor) : ProductorRepository {
    private val data = initial.associateBy(Productor::id)
    override suspend fun obtenerPorId(id: String): Productor? = data[id]
    override suspend fun obtenerTodos(): List<Productor> = data.values.toList()
    override suspend fun guardar(productor: Productor): Productor = productor
}

private class FakeConsultaEntregaRepository(initial: List<Entrega>) : EntregaRepository {
    private val data = initial.toMutableList()
    override suspend fun obtenerPorId(id: String): Entrega? = data.find { it.id == id }
    override suspend fun obtenerTodas(): List<Entrega> = data.toList()
    override suspend fun guardar(entrega: Entrega): Entrega = entrega.also { data += it }
    override suspend fun obtenerPorProductor(productorId: String, rango: RangoFechas?): List<Entrega> = data.filter {
        it.productorId == productorId && (rango == null || it.fechaHora in rango)
    }
    override suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega> = data.filter { it.fechaHora in rango }
}
