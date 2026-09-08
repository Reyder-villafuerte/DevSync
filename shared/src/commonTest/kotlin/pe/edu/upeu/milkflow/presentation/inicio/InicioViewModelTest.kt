package pe.edu.upeu.milkflow.presentation.inicio

import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue
import kotlin.test.fail
import kotlin.time.Instant
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import kotlinx.datetime.TimeZone
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.RegistroSincronizable
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.TipoProducto
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.LoteProduccionRepository
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasRecientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerLotesProduccion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenCalidad
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenProduccion
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

@OptIn(ExperimentalCoroutinesApi::class)
class InicioViewModelTest {
    private val dispatcher = StandardTestDispatcher()
    private val now = Instant.parse("2026-09-01T15:00:00Z")

    @BeforeTest
    fun setUp() {
        Dispatchers.setMain(dispatcher)
    }

    @AfterTest
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `estado inicial es Loading`() {
        val viewModel = createViewModel(createSession(RolUsuario.ADMINISTRADORA))
        assertEquals(InicioContentState.Loading, viewModel.uiState.value.content)
    }

    @Test
    fun `JEFE_PRODUCCION ve Registrar Lote de Produccion y NO ve Registrar Entrega`() = runTest(dispatcher) {
        val session = createSession(RolUsuario.JEFE_PRODUCCION)
        val viewModel = createViewModel(session)
        
        advanceUntilIdle()

        val data = viewModel.uiState.value.getDashboardData()
        assertTrue(data.acciones.any { it.navigation == InicioNavigation.REGISTRAR_LOTE })
        assertTrue(data.acciones.none { it.navigation == InicioNavigation.REGISTRAR_ENTREGA })
    }

    @Test
    fun `ACOPIADOR ve Nueva recoleccion`() = runTest(dispatcher) {
        val session = createSession(RolUsuario.ACOPIADOR)
        val viewModel = createViewModel(session)
        
        advanceUntilIdle()

        val data = viewModel.uiState.value.getDashboardData()
        assertTrue(data.kpis.any { it.titulo.contains("recogidos") })
        assertTrue(data.acciones.any { it.titulo.contains("recolección") })
    }

    @Test
    fun `PRODUCTOR ve Home orientado a consulta`() = runTest(dispatcher) {
        val session = createSession(RolUsuario.PRODUCTOR)
        val viewModel = createViewModel(session)
        
        advanceUntilIdle()

        val data = viewModel.uiState.value.getDashboardData()
        assertTrue(data.esProductor)
        assertTrue(data.acciones.any { it.navigation == InicioNavigation.MIS_ENTREGAS })
    }

    @Test
    fun `carga resumen entregas para administradora`() = runTest(dispatcher) {
        val entrega = entrega("e-1", 10.0, "2026-09-01T10:00:00Z")
        val session = createSession(RolUsuario.ADMINISTRADORA)
        val viewModel = createViewModel(session, FakeInicioEntregaRepository(entrega))

        advanceUntilIdle()

        val data = viewModel.uiState.value.getDashboardData()
        assertTrue(data.kpis.any { it.valor == "10.0 L" })
    }

    @Test
    fun `muestra error cuando no existe sesion`() = runTest(dispatcher) {
        val viewModel = createViewModel(SesionUsuario())
        advanceUntilIdle()
        assertIs<InicioContentState.Error>(viewModel.uiState.value.content)
    }

    @Test
    fun `JEFE_PRODUCCION ve KPIs de produccion reales`() = runTest(dispatcher) {
        val session = createSession(RolUsuario.JEFE_PRODUCCION)
        val lote = LoteProduccion("l-1", now, 100.0, 12, TipoProducto.QUESO_PARIA_FRESCO)
        val repo = FakeInicioLoteRepository(lote)
        val viewModel = createViewModel(session, produccion = repo)
        
        advanceUntilIdle()

        val data = viewModel.uiState.value.getDashboardData()
        assertTrue(data.kpis.any { it.titulo.contains("Leche procesada") && it.valor == "100.0 L" })
        assertTrue(data.kpis.any { it.titulo.contains("Quesos producidos") && it.valor == "12" })
        assertTrue(data.kpis.any { it.titulo.contains("Rendimiento") && it.valor == "12.0 m/100L" })
        assertTrue(data.entregasRecientes.any { it.tipo.contains("paria fresco") })
    }

    @Test
    fun `SUPERVISOR ve KPI de muestras con rango local`() = runTest(dispatcher) {
        val zone = TimeZone.UTC
        val testDate = Instant.parse("2026-09-01T15:00:00Z") // In middle of range
        val session = createSession(RolUsuario.SUPERVISOR)
        val test = PruebaCalidad("q-1", "e-1", testDate)
        val repo = FakeInicioCalidadRepository(test)
        
        val viewModel = InicioViewModel(
            sesionUsuario = session,
            obtenerReporteDiario = ObtenerReporteDiario(FakeInicioEntregaRepository()),
            obtenerEntregasRecientes = ObtenerEntregasRecientes(FakeInicioEntregaRepository()),
            obtenerRegistrosPendientes = ObtenerRegistrosPendientes(FakeInicioSincronizacionRepository()),
            obtenerResumenProduccion = ObtenerResumenProduccion(FakeInicioLoteRepository()),
            obtenerLotesProduccion = ObtenerLotesProduccion(FakeInicioLoteRepository()),
            obtenerResumenCalidad = ObtenerResumenCalidad(repo),
            ahora = { testDate },
            zonaHoraria = { zone },
        )
        
        advanceUntilIdle()

        val data = viewModel.uiState.value.getDashboardData()
        assertTrue(data.kpis.any { it.titulo.contains("Muestras") && it.valor == "1" })
    }

    private fun InicioUiState.getDashboardData(): InicioDashboardData = when (val c = content) {
        is InicioContentState.Success -> c.data
        is InicioContentState.Empty -> c.data
        else -> fail("Expected Success or Empty but was $content")
    }

    private fun createSession(rol: RolUsuario) = SesionUsuario().apply {
        iniciar(
            Usuario(
                id = "u-1",
                nombreUsuario = "test",
                nombre = "Test User",
                rol = rol,
                activo = true,
            )
        )
    }

    private fun createViewModel(
        session: SesionUsuario,
        entregas: EntregaRepository = FakeInicioEntregaRepository(),
        produccion: LoteProduccionRepository = FakeInicioLoteRepository(),
        calidad: CalidadRepository = FakeInicioCalidadRepository()
    ): InicioViewModel {
        return InicioViewModel(
            sesionUsuario = session,
            obtenerReporteDiario = ObtenerReporteDiario(entregas),
            obtenerEntregasRecientes = ObtenerEntregasRecientes(entregas),
            obtenerRegistrosPendientes = ObtenerRegistrosPendientes(FakeInicioSincronizacionRepository()),
            obtenerResumenProduccion = ObtenerResumenProduccion(produccion),
            obtenerLotesProduccion = ObtenerLotesProduccion(produccion),
            obtenerResumenCalidad = ObtenerResumenCalidad(calidad),
            ahora = { now },
            zonaHoraria = { TimeZone.UTC },
        )
    }

private class FakeInicioCalidadRepository(
    vararg initial: PruebaCalidad
) : CalidadRepository {
    private val pruebas = initial.toList()
    override suspend fun obtenerPruebaPorId(id: String) = null
    override suspend fun obtenerPruebaPorEntrega(entregaId: String) = null
    override suspend fun obtenerProblemasPorEntrega(entregaId: String) = emptyList<ProblemaLeche>()
    override suspend fun obtenerPruebasPorRango(rango: RangoFechas) = pruebas.filter { it.fechaHora in rango }
    override suspend fun obtenerProblemasPorRango(rango: RangoFechas) = emptyList<ProblemaLeche>()
    override suspend fun obtenerTodasLasPruebas() = pruebas.toList()
    override suspend fun obtenerTodosLosProblemas() = emptyList<ProblemaLeche>()
    override suspend fun guardarPrueba(prueba: PruebaCalidad) = prueba
    override suspend fun guardarProblema(problema: ProblemaLeche) = problema
}

private class FakeInicioLoteRepository(
    vararg initial: LoteProduccion
) : LoteProduccionRepository {
    private val lotes = initial.toList()
    override suspend fun guardar(lote: LoteProduccion) = lote
    override suspend fun obtenerPorId(id: String) = null
    override suspend fun obtenerPorRango(rango: RangoFechas) = lotes.filter { it.fechaHora in rango }
    override fun observarLotes() = flowOf(lotes)
}

    private fun entrega(id: String, litros: Double, fecha: String) = Entrega(
        id = id,
        productorId = "p-1",
        fechaHora = Instant.parse(fecha),
        litros = litros,
        tipo = TipoEntrega.DIRECTA,
        usuarioRegistroId = "u-1",
    )
}

private class FakeInicioEntregaRepository(
    vararg entregas: Entrega,
) : EntregaRepository {
    private val datos = entregas.toMutableList()
    override suspend fun obtenerPorId(id: String): Entrega? = datos.find { it.id == id }
    override suspend fun obtenerTodas(): List<Entrega> = datos.toList()
    override suspend fun guardar(entrega: Entrega): Entrega = entrega.also(datos::add)
    override suspend fun obtenerPorProductor(productorId: String, rango: RangoFechas?): List<Entrega> = emptyList()
    override suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega> = datos.filter { it.fechaHora in rango }
}

private class FakeInicioSincronizacionRepository : SincronizacionRepository {
    override fun observarRegistros() = flowOf(emptyList<RegistroSincronizable>())
    override suspend fun obtenerRegistros() = emptyList<RegistroSincronizable>()
    override fun observarPendientes() = flowOf(emptyList<RegistroSincronizable>())
    override suspend fun obtenerPendientes() = emptyList<RegistroSincronizable>()
    override suspend fun sincronizar(registro: RegistroSincronizable) = EstadoSincronizacion.ENVIADO
    override suspend fun obtenerEstado(tipoRegistro: String, registroId: String) = null
    override suspend fun actualizarEstado(tipoRegistro: String, registroId: String, estado: EstadoSincronizacion) = false
}
