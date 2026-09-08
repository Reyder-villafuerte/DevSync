package pe.edu.upeu.milkflow.presentation.entrega

import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Instant
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregas
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarEntregaDirecta
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLecheRecogida
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

@OptIn(ExperimentalCoroutinesApi::class)
class EntregaViewModelTest {
    private val dispatcher = StandardTestDispatcher()
    private val fecha = Instant.parse("2026-09-01T15:30:00Z")

    @BeforeTest
    fun setUp() {
        Dispatchers.setMain(dispatcher)
    }

    @AfterTest
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun registrarDirectaConProductorActivoYEstadoPendiente() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()

        fixture.viewModel.onEvent(EntregaUiEvent.SelectProductor("p-activo"))
        fixture.viewModel.onEvent(EntregaUiEvent.LitrosChanged("18.5"))
        fixture.viewModel.onEvent(EntregaUiEvent.Save)
        advanceUntilIdle()

        val entrega = fixture.entregas.guardadas.single()
        assertEquals(TipoEntrega.DIRECTA, entrega.tipo)
        assertEquals(18.5, entrega.litros)
        assertEquals(fecha, entrega.fechaHora)
        assertEquals("u-1", entrega.usuarioRegistroId)
        assertEquals(EstadoSincronizacion.PENDIENTE, entrega.estadoSincronizacion)
        assertNull(entrega.acopiadorId)
        assertIs<EntregaSubmissionState.Success>(fixture.viewModel.uiState.value.submission)
    }

    @Test
    fun rechazarProductorInactivo() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()

        fixture.viewModel.onEvent(EntregaUiEvent.SelectProductor("p-inactivo"))
        fixture.viewModel.onEvent(EntregaUiEvent.LitrosChanged("10"))
        assertFalse(fixture.viewModel.uiState.value.formularioValido)
        fixture.viewModel.onEvent(EntregaUiEvent.Save)
        advanceUntilIdle()

        assertTrue(fixture.entregas.guardadas.isEmpty())
        val error = assertIs<EntregaSubmissionState.Error>(fixture.viewModel.uiState.value.submission)
        assertTrue(error.message.contains("inactivo"))
    }

    @Test
    fun rechazarLitrosMenoresOIgualesACero() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()
        fixture.viewModel.onEvent(EntregaUiEvent.SelectProductor("p-activo"))

        fixture.viewModel.onEvent(EntregaUiEvent.LitrosChanged("0"))
        fixture.viewModel.onEvent(EntregaUiEvent.Save)

        assertTrue(fixture.entregas.guardadas.isEmpty())
        assertIs<EntregaSubmissionState.Error>(fixture.viewModel.uiState.value.submission)
    }

    @Test
    fun registrarRecogidaValida() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()

        fixture.viewModel.onEvent(EntregaUiEvent.TipoChanged(TipoEntrega.RECOGIDA))
        fixture.viewModel.onEvent(EntregaUiEvent.SelectProductor("p-activo"))
        fixture.viewModel.onEvent(EntregaUiEvent.LitrosChanged("24.75"))
        fixture.viewModel.onEvent(EntregaUiEvent.SelectAcopiador("a-1"))
        fixture.viewModel.onEvent(EntregaUiEvent.SectorChanged("Sector Norte"))
        assertTrue(fixture.viewModel.uiState.value.formularioValido)
        fixture.viewModel.onEvent(EntregaUiEvent.Save)
        advanceUntilIdle()

        val entrega = fixture.entregas.guardadas.single()
        assertEquals(TipoEntrega.RECOGIDA, entrega.tipo)
        assertEquals("a-1", entrega.acopiadorId)
        assertEquals("Sector Norte", entrega.sector)
        assertEquals(EstadoSincronizacion.PENDIENTE, entrega.estadoSincronizacion)
    }

    @Test
    fun cambiarADirectaLimpiaDatosDeRecogida() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()
        fixture.viewModel.onEvent(EntregaUiEvent.TipoChanged(TipoEntrega.RECOGIDA))
        fixture.viewModel.onEvent(EntregaUiEvent.SelectAcopiador("a-1"))
        fixture.viewModel.onEvent(EntregaUiEvent.SectorChanged("Sector Norte"))

        fixture.viewModel.onEvent(EntregaUiEvent.TipoChanged(TipoEntrega.DIRECTA))

        assertEquals(TipoEntrega.DIRECTA, fixture.viewModel.uiState.value.tipo)
        assertNull(fixture.viewModel.uiState.value.acopiadorId)
        assertEquals("", fixture.viewModel.uiState.value.sector)
    }

    @Test
    fun recogidaIncompletaNoHabilitaFormulario() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()
        fixture.viewModel.onEvent(EntregaUiEvent.TipoChanged(TipoEntrega.RECOGIDA))
        fixture.viewModel.onEvent(EntregaUiEvent.SelectProductor("p-activo"))
        fixture.viewModel.onEvent(EntregaUiEvent.LitrosChanged("12"))

        assertFalse(fixture.viewModel.uiState.value.formularioValido)
        fixture.viewModel.onEvent(EntregaUiEvent.Save)

        assertIs<EntregaSubmissionState.Error>(fixture.viewModel.uiState.value.submission)
    }

    @Test
    fun procesaEventosDelFormulario() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()

        fixture.viewModel.onEvent(EntregaUiEvent.SelectProductor("p-activo"))
        fixture.viewModel.onEvent(EntregaUiEvent.LitrosChanged("15.25"))
        fixture.viewModel.onEvent(EntregaUiEvent.TipoChanged(TipoEntrega.RECOGIDA))
        fixture.viewModel.onEvent(EntregaUiEvent.SelectAcopiador("a-1"))
        fixture.viewModel.onEvent(EntregaUiEvent.SectorChanged("Centro"))

        val state = fixture.viewModel.uiState.value
        assertEquals("p-activo", state.productorId)
        assertEquals("15.25", state.litros)
        assertEquals(TipoEntrega.RECOGIDA, state.tipo)
        assertEquals("a-1", state.acopiadorId)
        assertEquals("Centro", state.sector)
    }

    @Test
    fun manejarErrorDeRegistro() = runTest(dispatcher) {
        val fixture = fixture(fallarAlGuardar = true)
        advanceUntilIdle()
        fixture.viewModel.onEvent(EntregaUiEvent.SelectProductor("p-activo"))
        fixture.viewModel.onEvent(EntregaUiEvent.LitrosChanged("15"))

        fixture.viewModel.onEvent(EntregaUiEvent.Save)
        advanceUntilIdle()

        assertIs<EntregaSubmissionState.Error>(fixture.viewModel.uiState.value.submission)
    }

    @Test
    fun listarEntregasConProductorYTipo() = runTest(dispatcher) {
        val existente = Entrega(
            id = "e-existente",
            productorId = "p-activo",
            fechaHora = fecha,
            litros = 21.0,
            tipo = TipoEntrega.RECOGIDA,
            usuarioRegistroId = "u-1",
            acopiadorId = "a-1",
            sector = "Sur",
        )
        val fixture = fixture(entregasIniciales = arrayOf(existente))

        advanceUntilIdle()

        val content = assertIs<EntregaContentState.Success>(fixture.viewModel.uiState.value.content)
        val item = content.entregas.single()
        assertEquals("Ana", item.productorNombre)
        assertEquals(TipoEntrega.RECOGIDA, item.tipo)
        assertEquals("Luis", item.acopiadorNombre)
        assertEquals("Sur", item.sector)
    }

    @Test
    fun acopiadorTieneTipoRecogidaFijoYTituloCorrecto() = runTest(dispatcher) {
        val productores = FakeEntregaProductorRepository(Productor("p-1", "Ana", true))
        val acopiadores = FakeEntregaAcopiadorRepository(Acopiador("a-1", "Luis"))
        val entregas = FakeEntregaPresentationRepository()
        val session = SesionUsuario().apply {
            iniciar(Usuario("u-2", "acop", "Juan", RolUsuario.ACOPIADOR, true))
        }
        val viewModel = EntregaViewModel(
            obtenerEntregas = ObtenerEntregas(entregas),
            productorRepository = productores,
            acopiadorRepository = acopiadores,
            registrarEntregaDirecta = RegistrarEntregaDirecta(productores, entregas),
            registrarLecheRecogida = RegistrarLecheRecogida(productores, acopiadores, entregas),
            sesionUsuario = session,
            validarPermisoUsuario = ValidarPermisoUsuario(),
            registrarAuditoria = RegistrarAuditoria(FakeAuditoriaRepository()),
            ahora = { fecha },
            idGenerator = { "e-1" }
        )
        
        advanceUntilIdle()
        
        val state = viewModel.uiState.value
        assertEquals(listOf(TipoEntrega.RECOGIDA), state.tiposDisponibles)
        assertEquals(TipoEntrega.RECOGIDA, state.tipo)
        assertEquals("Nueva recolección", state.titulo)
    }

    private fun fixture(
        fallarAlGuardar: Boolean = false,
        entregasIniciales: Array<Entrega> = emptyArray(),
    ): EntregaFixture {
        val productores = FakeEntregaProductorRepository(
            Productor("p-activo", "Ana", true),
            Productor("p-inactivo", "Pedro", false),
        )
        val acopiadores = FakeEntregaAcopiadorRepository(Acopiador("a-1", "Luis"))
        val entregas = FakeEntregaPresentationRepository(
            *entregasIniciales,
            fallarAlGuardar = fallarAlGuardar,
        )
        val auditoria = FakeAuditoriaRepository()
        val session = SesionUsuario().apply {
            iniciar(
                Usuario(
                    id = "u-1",
                    nombreUsuario = "operador",
                    nombre = "María",
                    rol = RolUsuario.ADMINISTRADORA,
                    activo = true,
                ),
            )
        }
        return EntregaFixture(
            entregas = entregas,
            viewModel = EntregaViewModel(
                obtenerEntregas = ObtenerEntregas(entregas),
                productorRepository = productores,
                acopiadorRepository = acopiadores,
                registrarEntregaDirecta = RegistrarEntregaDirecta(productores, entregas),
                registrarLecheRecogida = RegistrarLecheRecogida(
                    productores,
                    acopiadores,
                    entregas,
                ),
                sesionUsuario = session,
                validarPermisoUsuario = ValidarPermisoUsuario(),
                registrarAuditoria = RegistrarAuditoria(auditoria),
                ahora = { fecha },
                idGenerator = { "e-nueva" },
            ),
        )
    }
}

private data class EntregaFixture(
    val viewModel: EntregaViewModel,
    val entregas: FakeEntregaPresentationRepository,
)

private class FakeEntregaProductorRepository(
    vararg productores: Productor,
) : ProductorRepository {
    private val datos = productores.associateBy(Productor::id).toMutableMap()

    override suspend fun obtenerPorId(id: String): Productor? = datos[id]
    override suspend fun obtenerTodos(): List<Productor> = datos.values.toList()
    override suspend fun guardar(productor: Productor): Productor = productor.also {
        datos[it.id] = it
    }
}

private class FakeEntregaAcopiadorRepository(
    vararg acopiadores: Acopiador,
) : AcopiadorRepository {
    private val datos = acopiadores.associateBy(Acopiador::id).toMutableMap()

    override suspend fun obtenerPorId(id: String): Acopiador? = datos[id]
    override suspend fun obtenerTodos(): List<Acopiador> = datos.values.toList()
    override suspend fun guardar(acopiador: Acopiador): Acopiador = acopiador.also {
        datos[it.id] = it
    }
}

private class FakeEntregaPresentationRepository(
    vararg entregas: Entrega,
    private val fallarAlGuardar: Boolean = false,
) : EntregaRepository {
    val guardadas = entregas.toMutableList()

    override suspend fun obtenerPorId(id: String): Entrega? = guardadas.find { it.id == id }
    override suspend fun obtenerTodas(): List<Entrega> = guardadas.toList()
    override suspend fun guardar(entrega: Entrega): Entrega {
        if (fallarAlGuardar) error("Fallo controlado")
        guardadas += entrega
        return entrega
    }
    override suspend fun obtenerPorProductor(
        productorId: String,
        rango: RangoFechas?,
    ): List<Entrega> = guardadas.filter {
        it.productorId == productorId && (rango == null || it.fechaHora in rango)
    }
    override suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega> =
        guardadas.filter { it.fechaHora in rango }
}

private class FakeAuditoriaRepository : AuditoriaRepository {
    private val registros = mutableListOf<RegistroAuditoria>()
    override suspend fun obtenerPorId(id: String): RegistroAuditoria? = registros.find { it.id == id }
    override suspend fun guardar(registro: RegistroAuditoria): RegistroAuditoria = registro.also { registros += it }
    override fun observarTodos() = kotlinx.coroutines.flow.flowOf(registros.toList())
}

