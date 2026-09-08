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
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregas
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarProblemaLeche
import pe.edu.upeu.milkflow.domain.usecase.RegistrarPruebaCalidad
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

@OptIn(ExperimentalCoroutinesApi::class)
class CalidadViewModelTest {
    private val dispatcher = StandardTestDispatcher()
    private val fechaRegistro = Instant.parse("2026-09-07T19:31:00Z")
    private val entrega = Entrega(
        id = "e-1",
        productorId = "p-1",
        fechaHora = Instant.parse("2026-09-01T10:00:00Z"),
        litros = 18.0,
        tipo = TipoEntrega.DIRECTA,
        usuarioRegistroId = "u-1",
    )

    @BeforeTest
    fun setUp() { Dispatchers.setMain(dispatcher) }

    @AfterTest
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun procesaSeleccionYRegistraPruebaVinculada() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()

        fixture.viewModel.onEvent(CalidadUiEvent.SelectEntrega("e-1"))
        advanceUntilIdle()
        fixture.viewModel.onEvent(CalidadUiEvent.RegistrarPrueba)
        advanceUntilIdle()

        val detail = assertIs<CalidadDetalleState.Loaded>(fixture.viewModel.uiState.value.detalle)
        assertEquals("e-1", detail.prueba?.entregaId)
        assertEquals(fechaRegistro, detail.prueba?.fechaHora)
        assertEquals(fechaRegistro, fixture.calidad.pruebas.single().fechaHora)
        assertIs<CalidadSubmissionState.Success>(fixture.viewModel.uiState.value.submission)
    }

    @Test
    fun noPermitePruebaSinEntregaSeleccionada() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()

        fixture.viewModel.onEvent(CalidadUiEvent.RegistrarPrueba)

        val error = assertIs<CalidadSubmissionState.Error>(fixture.viewModel.uiState.value.submission)
        assertTrue(error.message.contains("Selecciona"))
        assertTrue(fixture.calidad.pruebas.isEmpty())
    }

    @Test
    fun registraProblemaConFechaHora() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()
        fixture.viewModel.onEvent(CalidadUiEvent.SelectEntrega("e-1"))
        advanceUntilIdle()
        fixture.viewModel.onEvent(CalidadUiEvent.ObservacionChanged("Revisar apariencia"))
        fixture.viewModel.onEvent(CalidadUiEvent.RegistrarProblema)
        advanceUntilIdle()

        assertEquals(entrega, fixture.entregas.obtenerPorId("e-1"))
        val problema = fixture.calidad.problemas.single()
        assertEquals("Revisar apariencia", problema.descripcion)
        assertEquals("e-1", problema.entregaId)
    }

    @Test
    fun listaEntregasDisponiblesParaRevision() = runTest(dispatcher) {
        val fixture = fixture()
        advanceUntilIdle()

        val content = assertIs<CalidadContentState.Success>(fixture.viewModel.uiState.value.content)
        assertEquals(listOf("e-1"), content.entregas.map { it.id })
        assertEquals(18.0, content.entregas.single().litros)
    }

    @Test
    fun pruebaExistenteSeMuestraYNoSeDuplica() = runTest(dispatcher) {
        val fixture = fixture(existing = PruebaCalidad("q-existente", "e-1", Instant.parse("2026-09-01T08:00:00Z")))
        advanceUntilIdle()
        fixture.viewModel.onEvent(CalidadUiEvent.SelectEntrega("e-1"))
        advanceUntilIdle()

        fixture.viewModel.onEvent(CalidadUiEvent.RegistrarPrueba)

        val error = assertIs<CalidadSubmissionState.Error>(fixture.viewModel.uiState.value.submission)
        assertTrue(error.message.contains("ya tiene"))
        assertEquals(1, fixture.calidad.pruebas.size)
    }

    private fun fixture(existing: PruebaCalidad? = null): CalidadFixture {
        val entregas = FakeCalidadEntregaRepository(entrega)
        val calidad = FakeCalidadRepository(existing)
        val auditoria = FakeAuditoriaRepository()
        val session = SesionUsuario().apply {
            iniciar(
                Usuario(
                    id = "u-1",
                    nombreUsuario = "calidad",
                    nombre = "Carlos",
                    rol = RolUsuario.SUPERVISOR,
                    activo = true,
                ),
            )
        }
        return CalidadFixture(
            entregas = entregas,
            calidad = calidad,
            viewModel = CalidadViewModel(
                obtenerEntregas = ObtenerEntregas(entregas),
                calidadRepository = calidad,
                registrarPruebaCalidad = RegistrarPruebaCalidad(entregas, calidad),
                registrarProblemaLeche = RegistrarProblemaLeche(entregas, calidad),
                sesionUsuario = session,
                validarPermisoUsuario = ValidarPermisoUsuario(),
                registrarAuditoria = RegistrarAuditoria(auditoria),
                ahora = { fechaRegistro },
                idGenerator = { "q-1" },
            ),
        )
    }
}

private data class CalidadFixture(
    val viewModel: CalidadViewModel,
    val entregas: FakeCalidadEntregaRepository,
    val calidad: FakeCalidadRepository,
)

private class FakeCalidadEntregaRepository(vararg initial: Entrega) : EntregaRepository {
    private val data = initial.toMutableList()
    override suspend fun obtenerPorId(id: String): Entrega? = data.find { it.id == id }
    override suspend fun obtenerTodas(): List<Entrega> = data.toList()
    override suspend fun guardar(entrega: Entrega): Entrega = entrega.also { data += it }
    override suspend fun obtenerPorProductor(productorId: String, rango: RangoFechas?): List<Entrega> =
        data.filter { it.productorId == productorId && (rango == null || it.fechaHora in rango) }
    override suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega> = data.filter { it.fechaHora in rango }
}

private class FakeCalidadRepository(existing: PruebaCalidad? = null) : CalidadRepository {
    val pruebas = existing?.let(::mutableListOf) ?: mutableListOf()
    val problemas = mutableListOf<ProblemaLeche>()
    override suspend fun obtenerPruebaPorId(id: String): PruebaCalidad? = pruebas.find { it.id == id }
    override suspend fun obtenerPruebaPorEntrega(entregaId: String): PruebaCalidad? = pruebas.find { it.entregaId == entregaId }
    override suspend fun obtenerProblemasPorEntrega(entregaId: String): List<ProblemaLeche> = problemas.filter { it.entregaId == entregaId }
    override suspend fun obtenerPruebasPorRango(rango: RangoFechas) = pruebas.filter { it.fechaHora in rango }
    override suspend fun obtenerProblemasPorRango(rango: RangoFechas) = problemas.filter { it.fechaHora in rango }
    override suspend fun obtenerTodasLasPruebas() = pruebas.toList()
    override suspend fun obtenerTodosLosProblemas() = problemas.toList()
    override suspend fun guardarPrueba(prueba: PruebaCalidad): PruebaCalidad = prueba.also { pruebas += it }
    override suspend fun guardarProblema(problema: ProblemaLeche): ProblemaLeche = problema.also { problemas += it }
}

private class FakeAuditoriaRepository : AuditoriaRepository {
    private val registros = mutableListOf<RegistroAuditoria>()
    override suspend fun obtenerPorId(id: String): RegistroAuditoria? = registros.find { it.id == id }
    override suspend fun guardar(registro: RegistroAuditoria): RegistroAuditoria = registro.also { registros += it }
    override fun observarTodos() = kotlinx.coroutines.flow.flowOf(registros.toList())
}
