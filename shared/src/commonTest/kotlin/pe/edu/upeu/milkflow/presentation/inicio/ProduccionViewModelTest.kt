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
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoProducto
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.repository.LoteProduccionRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerLotesProduccion
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLoteProduccion
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

@OptIn(ExperimentalCoroutinesApi::class)
class ProduccionViewModelTest {
    private val dispatcher = StandardTestDispatcher()
    private val now = Instant.parse("2026-09-01T10:00:00Z")

    @BeforeTest
    fun setUp() {
        Dispatchers.setMain(dispatcher)
    }

    @AfterTest
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `registrar lote valido calcula rendimiento correctamente`() = runTest(dispatcher) {
        val repo = FakeLoteRepository()
        val viewModel = createViewModel(repo)
        
        viewModel.onEvent(ProduccionUiEvent.TipoProductoChanged(TipoProducto.QUESO_PARIA_FRESCO))
        viewModel.onEvent(ProduccionUiEvent.LitrosLecheChanged("100"))
        viewModel.onEvent(ProduccionUiEvent.MoldesChanged("12"))
        viewModel.onEvent(ProduccionUiEvent.RegistrarLote)
        
        advanceUntilIdle()

        assertEquals(1, repo.lotes.size)
        val guardado = repo.lotes.first()
        assertEquals(12.0, guardado.rendimiento)
        assertTrue(guardado.dentroDelRango == true)
        assertIs<ProduccionSubmissionState.Success>(viewModel.uiState.value.submission)
    }

    @Test
    fun `rechazar litros menores o iguales a cero`() = runTest(dispatcher) {
        val repo = FakeLoteRepository()
        val viewModel = createViewModel(repo)
        
        viewModel.onEvent(ProduccionUiEvent.LitrosLecheChanged("0"))
        viewModel.onEvent(ProduccionUiEvent.RegistrarLote)
        
        advanceUntilIdle()

        assertTrue(repo.lotes.isEmpty())
        assertIs<ProduccionSubmissionState.Error>(viewModel.uiState.value.submission)
    }

    @Test
    fun `detectar rendimiento fuera de rango`() = runTest(dispatcher) {
        val repo = FakeLoteRepository()
        val viewModel = createViewModel(repo)
        
        viewModel.onEvent(ProduccionUiEvent.TipoProductoChanged(TipoProducto.QUESO_PARIA_FRESCO))
        viewModel.onEvent(ProduccionUiEvent.LitrosLecheChanged("100"))
        viewModel.onEvent(ProduccionUiEvent.MoldesChanged("15")) // Rendimiento 15
        viewModel.onEvent(ProduccionUiEvent.RegistrarLote)
        
        advanceUntilIdle()

        val guardado = repo.lotes.first()
        assertEquals(15.0, guardado.rendimiento)
        assertEquals(false, guardado.dentroDelRango)
    }

    private fun createViewModel(repo: LoteProduccionRepository): ProduccionViewModel {
        val session = SesionUsuario().apply {
            iniciar(Usuario("u-1", "jefe", "Jefe", RolUsuario.JEFE_PRODUCCION, true))
        }
        return ProduccionViewModel(
            registrarLoteProduccion = RegistrarLoteProduccion(repo),
            obtenerLotesProduccion = ObtenerLotesProduccion(repo),
            sesionUsuario = session,
            registrarAuditoria = RegistrarAuditoria(FakeAuditRepo()),
            ahora = { now },
            idGenerator = { "l-1" }
        )
    }
}

private class FakeLoteRepository : LoteProduccionRepository {
    val lotes = mutableListOf<LoteProduccion>()
    override suspend fun guardar(lote: LoteProduccion) = lote.also { lotes.add(it) }
    override suspend fun obtenerPorId(id: String) = lotes.find { it.id == id }
    override suspend fun obtenerPorRango(rango: RangoFechas) = lotes.filter { it.fechaHora in rango }
    override fun observarLotes(): Flow<List<LoteProduccion>> = flowOf(lotes)
}

private class FakeAuditRepo : AuditoriaRepository {
    override suspend fun obtenerPorId(id: String) = null
    override suspend fun guardar(registro: RegistroAuditoria) = registro
    override fun observarTodos() = flowOf(emptyList<RegistroAuditoria>())
}
