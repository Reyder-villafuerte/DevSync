package pe.edu.upeu.milkflow.presentation.registro

import kotlin.test.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.*
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository
import pe.edu.upeu.milkflow.domain.usecase.RegistrarCuenta
import kotlinx.coroutines.flow.Flow

@OptIn(ExperimentalCoroutinesApi::class)
class RegistroViewModelTest {
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
    fun `estado inicial de registro`() = runTest(dispatcher) {
        val viewModel = createViewModel()
        val state = viewModel.uiState.value
        assertEquals("", state.nombreCompleto)
        assertEquals("", state.correo)
        assertEquals("", state.clave)
        assertEquals("", state.confirmarClave)
        assertEquals(RegistroSubmission.Idle, state.submission)
    }

    @Test
    fun `intentar registrar con campos vacios muestra error`() = runTest(dispatcher) {
        val viewModel = createViewModel()
        viewModel.onEvent(RegistroUiEvent.Registrar)
        advanceUntilIdle()

        val state = viewModel.uiState.value
        assertTrue(state.submission is RegistroSubmission.Error)
    }

    @Test
    fun `intentar registrar con contrasenas diferentes muestra error`() = runTest(dispatcher) {
        val viewModel = createViewModel()
        viewModel.onEvent(RegistroUiEvent.NombreCompletoChanged("Juan Perez"))
        viewModel.onEvent(RegistroUiEvent.CorreoChanged("juan@mail.com"))
        viewModel.onEvent(RegistroUiEvent.ClaveChanged("123456"))
        viewModel.onEvent(RegistroUiEvent.ConfirmarClaveChanged("654321"))
        
        viewModel.onEvent(RegistroUiEvent.Registrar)
        advanceUntilIdle()

        val state = viewModel.uiState.value
        assertTrue(state.submission is RegistroSubmission.Error)
        assertEquals("Las contraseñas no coinciden.", (state.submission as RegistroSubmission.Error).message)
    }

    @Test
    fun `registro valido cambia a estado Success`() = runTest(dispatcher) {
        val repository = FakeUsuarioRepository()
        val viewModel = createViewModel(repository)
        
        viewModel.onEvent(RegistroUiEvent.NombreCompletoChanged("Juan Perez"))
        viewModel.onEvent(RegistroUiEvent.CorreoChanged("juan@mail.com"))
        viewModel.onEvent(RegistroUiEvent.ClaveChanged("123456"))
        viewModel.onEvent(RegistroUiEvent.ConfirmarClaveChanged("123456"))
        
        viewModel.onEvent(RegistroUiEvent.Registrar)
        advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals(RegistroSubmission.Success, state.submission)
        assertEquals(1, repository.usuarios.size)
        val registrado = repository.usuarios.first()
        assertEquals("juan@mail.com", registrado.nombreUsuario)
        assertEquals(RolUsuario.PENDIENTE_ASIGNACION, registrado.rol)
    }

    @Test
    fun `cuenta pendiente lanza excepcion al autenticar`() = runTest(dispatcher) {
        val repository = FakeUsuarioRepository()
        val registrarCuenta = RegistrarCuenta(repository)
        repository.permitirAutenticacion = true
        registrarCuenta("test-id", "Juan", "juan@mail.com", "123456")
        
        val autenticar = pe.edu.upeu.milkflow.domain.usecase.AutenticarUsuario(repository)
        
        assertFailsWith<pe.edu.upeu.milkflow.domain.CuentaPendienteException> {
            autenticar("juan@mail.com", "123456")
        }
    }

    private fun createViewModel(repository: UsuarioRepository = FakeUsuarioRepository()): RegistroViewModel {
        return RegistroViewModel(
            registrarCuenta = RegistrarCuenta(repository),
            idGenerator = { "test-id" }
        )
    }
}

private class FakeUsuarioRepository : UsuarioRepository {
    val usuarios = mutableListOf<Usuario>()
    var permitirAutenticacion = false
    
    override suspend fun autenticar(nombreUsuario: String, clave: String): Usuario? {
        return if (permitirAutenticacion) {
            usuarios.find { it.nombreUsuario == nombreUsuario }
        } else null
    }
    override suspend fun obtenerPorId(id: String): Usuario? = usuarios.find { it.id == id }
    override suspend fun guardar(usuario: Usuario, clave: String?): Usuario {
        usuarios.add(usuario)
        return usuario
    }
    override fun observarTodos(): Flow<List<Usuario>> {
        throw NotImplementedError()
    }
}
