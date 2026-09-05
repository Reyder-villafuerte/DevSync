package pe.edu.upeu.milkflow.presentation.productor

import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertTrue
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ActualizarProductor
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarProductor
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

@OptIn(ExperimentalCoroutinesApi::class)
class ProductorViewModelTest {
    private val dispatcher = StandardTestDispatcher()

    @BeforeTest
    fun setUp() {
        Dispatchers.setMain(dispatcher)
    }

    @AfterTest
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun cargarProductores() = runTest(dispatcher) {
        val viewModel = createViewModel(
            FakeProductorPresentationRepository(
                Productor("p-2", "Zoila", true),
                Productor("p-1", "Ana", true),
            ),
        )

        advanceUntilIdle()

        val content = assertIs<ProductorContentState.Success>(viewModel.uiState.value.content)
        assertEquals(listOf("Ana", "Zoila"), content.productores.map { it.nombre })
    }

    @Test
    fun estadoVacio() = runTest(dispatcher) {
        val viewModel = createViewModel(FakeProductorPresentationRepository())

        advanceUntilIdle()

        assertEquals(ProductorContentState.Empty, viewModel.uiState.value.content)
    }

    @Test
    fun actualizarProductor() = runTest(dispatcher) {
        val repository = FakeProductorPresentationRepository(Productor("p-1", "Ana", true))
        val viewModel = createViewModel(repository)
        advanceUntilIdle()
        viewModel.onEvent(ProductorUiEvent.Select("p-1"))
        viewModel.onEvent(ProductorUiEvent.NombreChanged("Ana Torres"))
        viewModel.onEvent(ProductorUiEvent.ActivoChanged(false))

        viewModel.onEvent(ProductorUiEvent.Save)
        advanceUntilIdle()

        assertEquals(Productor("p-1", "Ana Torres", false), repository.obtenerPorId("p-1"))
        assertIs<ProductorSaveState.Success>(viewModel.uiState.value.saveState)
    }

    @Test
    fun registrarProductor() = runTest(dispatcher) {
        val repository = FakeProductorPresentationRepository()
        val viewModel = createViewModel(repository)
        advanceUntilIdle()
        
        viewModel.onEvent(ProductorUiEvent.Nuevo)
        viewModel.onEvent(ProductorUiEvent.NombreChanged("Nuevo Productor"))
        viewModel.onEvent(ProductorUiEvent.Registrar)
        advanceUntilIdle()
        
        assertEquals(1, repository.productores.size)
        assertEquals("Nuevo Productor", repository.productores.values.first().nombre)
        assertIs<ProductorSaveState.Success>(viewModel.uiState.value.saveState)
    }

    @Test
    fun identificarProductorActivo() = runTest(dispatcher) {
        val viewModel = createViewModel(
            FakeProductorPresentationRepository(Productor("p-1", "Ana", true)),
        )
        advanceUntilIdle()

        viewModel.onEvent(ProductorUiEvent.Select("p-1"))

        assertTrue(viewModel.uiState.value.activoEditado)
    }

    @Test
    fun identificarProductorInactivo() = runTest(dispatcher) {
        val viewModel = createViewModel(
            FakeProductorPresentationRepository(Productor("p-1", "Ana", false)),
        )
        advanceUntilIdle()

        viewModel.onEvent(ProductorUiEvent.Select("p-1"))

        assertFalse(viewModel.uiState.value.activoEditado)
    }

    private fun createViewModel(repository: FakeProductorPresentationRepository): ProductorViewModel {
        val auditoria = FakeAuditoriaRepository()
        val session = SesionUsuario().apply {
            iniciar(
                Usuario(
                    id = "u-1",
                    nombreUsuario = "admin",
                    nombre = "Admin",
                    rol = RolUsuario.ADMINISTRADORA,
                    activo = true,
                ),
            )
        }
        return ProductorViewModel(
            productorRepository = repository,
            actualizarProductor = ActualizarProductor(repository),
            registrarProductor = RegistrarProductor(repository),
            sesionUsuario = session,
            validarPermisoUsuario = ValidarPermisoUsuario(),
            registrarAuditoria = RegistrarAuditoria(auditoria),
        )
    }
}

private class FakeProductorPresentationRepository(
    vararg initialProductores: Productor,
) : ProductorRepository {
    val productores = initialProductores.associateBy(Productor::id).toMutableMap()

    override suspend fun obtenerPorId(id: String): Productor? = productores[id]

    override suspend fun obtenerTodos(): List<Productor> = productores.values.toList()

    override suspend fun guardar(productor: Productor): Productor =
        productor.also { productores[it.id] = it }
}

private class FakeAuditoriaRepository : AuditoriaRepository {
    private val registros = mutableListOf<RegistroAuditoria>()
    override suspend fun obtenerPorId(id: String): RegistroAuditoria? = registros.find { it.id == id }
    override suspend fun guardar(registro: RegistroAuditoria): RegistroAuditoria = registro.also { registros += it }
    override fun observarTodos() = kotlinx.coroutines.flow.flowOf(registros.toList())
}
