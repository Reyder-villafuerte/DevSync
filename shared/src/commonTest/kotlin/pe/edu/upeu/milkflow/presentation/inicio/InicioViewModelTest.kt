package pe.edu.upeu.milkflow.presentation.inicio

import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue
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
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.RegistroSincronizable
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasRecientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
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
    fun estadoInicialEsLoading() {
        val viewModel = createViewModel(
            entregas = FakeInicioEntregaRepository(),
            sincronizacion = FakeInicioSincronizacionRepository(),
        )

        assertEquals(InicioContentState.Loading, viewModel.uiState.value.content)
    }

    @Test
    fun cargaResumenEntregasRecientesYPendientes() = runTest(dispatcher) {
        val antigua = entrega("e-1", 12.5, "2026-09-01T08:00:00Z")
        val reciente = entrega("e-2", 20.0, "2026-09-01T14:00:00Z")
        val viewModel = createViewModel(
            entregas = FakeInicioEntregaRepository(antigua, reciente),
            sincronizacion = FakeInicioSincronizacionRepository(
                RegistroSincronizable("e-2", "ENTREGA", EstadoSincronizacion.PENDIENTE),
            ),
        )

        advanceUntilIdle()

        val state = assertIs<InicioContentState.Success>(viewModel.uiState.value.content)
        assertEquals("María Operadora", state.data.nombreUsuario)
        assertEquals("Jefe de producción", state.data.rol)
        assertEquals(32.5, state.data.totalLitrosHoy)
        assertEquals(2, state.data.cantidadEntregasHoy)
        assertEquals(1, state.data.registrosPendientes)
        assertEquals(listOf("e-2", "e-1"), state.data.entregasRecientes.map { it.id })
        assertTrue(state.data.acciones.none { it.navigation == InicioNavigation.USUARIOS })
    }

    @Test
    fun identificaAdministradoraParaGestionDeUsuarios() = runTest(dispatcher) {
        val session = SesionUsuario()
        session.iniciar(
            Usuario(
                id = "u-admin",
                nombreUsuario = "admin",
                nombre = "Admin",
                rol = RolUsuario.ADMINISTRADORA,
                activo = true,
            ),
        )
        val viewModel = InicioViewModel(
            sesionUsuario = session,
            obtenerReporteDiario = ObtenerReporteDiario(FakeInicioEntregaRepository()),
            obtenerEntregasRecientes = ObtenerEntregasRecientes(FakeInicioEntregaRepository()),
            obtenerRegistrosPendientes = ObtenerRegistrosPendientes(FakeInicioSincronizacionRepository()),
            ahora = { now },
            zonaHoraria = { TimeZone.UTC },
        )

        advanceUntilIdle()

        val state = assertIs<InicioContentState.Empty>(viewModel.uiState.value.content)
        assertTrue(state.data.acciones.any { it.navigation == InicioNavigation.USUARIOS })
    }

    @Test
    fun cargaEstadoVacioCuandoNoHayEntregas() = runTest(dispatcher) {
        val viewModel = createViewModel(
            entregas = FakeInicioEntregaRepository(),
            sincronizacion = FakeInicioSincronizacionRepository(),
        )

        advanceUntilIdle()

        val state = assertIs<InicioContentState.Empty>(viewModel.uiState.value.content)
        assertEquals(0.0, state.data.totalLitrosHoy)
        assertEquals(0, state.data.cantidadEntregasHoy)
        assertEquals(emptyList(), state.data.entregasRecientes)
    }

    @Test
    fun muestraErrorCuandoNoExisteSesion() = runTest(dispatcher) {
        val viewModel = createViewModel(
            entregas = FakeInicioEntregaRepository(),
            sincronizacion = FakeInicioSincronizacionRepository(),
            withSession = false,
        )

        advanceUntilIdle()

        assertIs<InicioContentState.Error>(viewModel.uiState.value.content)
    }

    private fun createViewModel(
        entregas: FakeInicioEntregaRepository,
        sincronizacion: FakeInicioSincronizacionRepository,
        withSession: Boolean = true,
    ): InicioViewModel {
        val session = SesionUsuario()
        if (withSession) {
            session.iniciar(
                Usuario(
                    id = "u-1",
                    nombreUsuario = "operador",
                    nombre = "María Operadora",
                    rol = RolUsuario.JEFE_PRODUCCION,
                    activo = true,
                ),
            )
        }
        return InicioViewModel(
            sesionUsuario = session,
            obtenerReporteDiario = ObtenerReporteDiario(entregas),
            obtenerEntregasRecientes = ObtenerEntregasRecientes(entregas),
            obtenerRegistrosPendientes = ObtenerRegistrosPendientes(sincronizacion),
            ahora = { now },
            zonaHoraria = { TimeZone.UTC },
        )
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

    override suspend fun obtenerPorProductor(
        productorId: String,
        rango: RangoFechas?,
    ): List<Entrega> = datos.filter {
        it.productorId == productorId && (rango == null || it.fechaHora in rango)
    }

    override suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega> =
        datos.filter { it.fechaHora in rango }
}

private class FakeInicioSincronizacionRepository(
    vararg pendientes: RegistroSincronizable,
) : SincronizacionRepository {
    private val datos = pendientes.toList()

    override fun observarRegistros(): Flow<List<RegistroSincronizable>> = flowOf(datos)

    override suspend fun obtenerRegistros(): List<RegistroSincronizable> = datos

    override fun observarPendientes(): Flow<List<RegistroSincronizable>> = flowOf(datos)

    override suspend fun obtenerPendientes(): List<RegistroSincronizable> = datos

    override suspend fun sincronizar(registro: RegistroSincronizable): EstadoSincronizacion =
        EstadoSincronizacion.ENVIADO

    override suspend fun obtenerEstado(
        tipoRegistro: String,
        registroId: String,
    ): EstadoSincronizacion? = datos.find {
        it.tipoRegistro == tipoRegistro && it.registroId == registroId
    }?.estado

    override suspend fun actualizarEstado(
        tipoRegistro: String,
        registroId: String,
        estado: EstadoSincronizacion,
    ): Boolean = false
}
