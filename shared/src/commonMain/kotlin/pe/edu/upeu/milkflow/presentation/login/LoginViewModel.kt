package pe.edu.upeu.milkflow.presentation.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.CredencialesInvalidasException
import pe.edu.upeu.milkflow.domain.CuentaPendienteException
import pe.edu.upeu.milkflow.domain.UsuarioInactivoException
import pe.edu.upeu.milkflow.domain.usecase.AutenticarUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class LoginViewModel(
    private val autenticarUsuario: AutenticarUsuario,
    private val sesionUsuario: SesionUsuario,
) : ViewModel() {
    private val _uiState = MutableStateFlow(LoginUiState())
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    fun onEvent(event: LoginUiEvent) {
        when (event) {
            is LoginUiEvent.NombreUsuarioChanged -> _uiState.update {
                it.copy(nombreUsuario = event.value, submission = LoginSubmission.Idle)
            }
            is LoginUiEvent.ClaveChanged -> _uiState.update {
                it.copy(clave = event.value, submission = LoginSubmission.Idle)
            }
            LoginUiEvent.IniciarSesion -> iniciarSesion()
        }
    }

    private fun iniciarSesion() {
        val state = _uiState.value
        if (state.submission == LoginSubmission.Loading) return

        val nombreUsuario = state.nombreUsuario.trim()
        if (nombreUsuario.isBlank()) {
            _uiState.update {
                it.copy(submission = LoginSubmission.Error("Ingresa tu nombre de usuario."))
            }
            return
        }
        if (state.clave.isBlank()) {
            _uiState.update {
                it.copy(submission = LoginSubmission.Error("Ingresa tu contraseña."))
            }
            return
        }

        _uiState.update {
            it.copy(nombreUsuario = nombreUsuario, submission = LoginSubmission.Loading)
        }
        viewModelScope.launch {
            try {
                val usuario = autenticarUsuario(nombreUsuario, state.clave)
                sesionUsuario.iniciar(usuario)
                _uiState.update { it.copy(submission = LoginSubmission.Success(usuario)) }
            } catch (error: CancellationException) {
                throw error
            } catch (_: CredencialesInvalidasException) {
                mostrarError("El usuario o la contraseña no son válidos.")
            } catch (error: CuentaPendienteException) {
                mostrarError(error.message ?: "Cuenta pendiente de asignación.")
            } catch (_: UsuarioInactivoException) {
                mostrarError("El usuario está inactivo. Comunícate con la administradora.")
            } catch (_: Exception) {
                mostrarError("No se pudo iniciar sesión. Inténtalo nuevamente.")
            }
        }
    }

    private fun mostrarError(message: String) {
        _uiState.update { it.copy(submission = LoginSubmission.Error(message)) }
    }
}
