package pe.edu.upeu.milkflow.presentation.login

import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertNull
import kotlin.test.assertSame
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository
import pe.edu.upeu.milkflow.domain.usecase.AutenticarUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest {
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
    fun estadoInicial() {
        val viewModel = createViewModel(FakeUsuarioRepository())

        assertEquals(LoginUiState(), viewModel.uiState.value)
    }

    @Test
    fun actualizaCamposMedianteEventos() {
        val viewModel = createViewModel(FakeUsuarioRepository())

        viewModel.onEvent(LoginUiEvent.NombreUsuarioChanged("operador"))
        viewModel.onEvent(LoginUiEvent.ClaveChanged("secreta"))

        assertEquals("operador", viewModel.uiState.value.nombreUsuario)
        assertEquals("secreta", viewModel.uiState.value.clave)
        assertEquals(LoginSubmission.Idle, viewModel.uiState.value.submission)
    }

    @Test
    fun intentoConFormularioInvalidoNoConsultaRepositorio() {
        val repository = FakeUsuarioRepository()
        val viewModel = createViewModel(repository)

        viewModel.onEvent(LoginUiEvent.IniciarSesion)

        assertEquals(0, repository.intentos)
        assertIs<LoginSubmission.Error>(viewModel.uiState.value.submission)
    }

    @Test
    fun autenticacionCorrectaConservaSesion() = runTest(dispatcher) {
        val usuario = usuarioActivo()
        val repository = FakeUsuarioRepository(usuario)
        val session = SesionUsuario()
        val viewModel = createViewModel(repository, session)
        viewModel.onEvent(LoginUiEvent.NombreUsuarioChanged(" operador "))
        viewModel.onEvent(LoginUiEvent.ClaveChanged("secreta"))

        viewModel.onEvent(LoginUiEvent.IniciarSesion)
        assertEquals(LoginSubmission.Loading, viewModel.uiState.value.submission)
        advanceUntilIdle()

        val success = assertIs<LoginSubmission.Success>(viewModel.uiState.value.submission)
        assertSame(usuario, success.usuario)
        assertSame(usuario, session.usuario.value)
        assertEquals("operador", viewModel.uiState.value.nombreUsuario)
        assertEquals(1, repository.intentos)
    }

    @Test
    fun autenticacionFallidaMuestraErrorYSinSesion() = runTest(dispatcher) {
        val session = SesionUsuario()
        val viewModel = createViewModel(FakeUsuarioRepository(), session)
        viewModel.onEvent(LoginUiEvent.NombreUsuarioChanged("desconocido"))
        viewModel.onEvent(LoginUiEvent.ClaveChanged("incorrecta"))

        viewModel.onEvent(LoginUiEvent.IniciarSesion)
        advanceUntilIdle()

        assertIs<LoginSubmission.Error>(viewModel.uiState.value.submission)
        assertNull(session.usuario.value)
    }

    private fun createViewModel(
        repository: FakeUsuarioRepository,
        session: SesionUsuario = SesionUsuario(),
    ) = LoginViewModel(AutenticarUsuario(repository), session)

    private fun usuarioActivo() = Usuario(
        id = "u-1",
        nombreUsuario = "operador",
        nombre = "María Operadora",
        rol = RolUsuario.JEFE_PRODUCCION,
        activo = true,
    )
}

private class FakeUsuarioRepository(
    private val usuario: Usuario? = null,
) : UsuarioRepository {
    var intentos: Int = 0
        private set

    override suspend fun autenticar(nombreUsuario: String, clave: String): Usuario? {
        intentos += 1
        return usuario?.takeIf {
            it.nombreUsuario == nombreUsuario && clave == "secreta"
        }
    }

    override suspend fun obtenerPorId(id: String): Usuario? = usuario?.takeIf { it.id == id }

    override suspend fun guardar(usuario: Usuario, clave: String?): Usuario = usuario

    override fun observarTodos(): Flow<List<Usuario>> = flowOf(listOfNotNull(usuario))
}
