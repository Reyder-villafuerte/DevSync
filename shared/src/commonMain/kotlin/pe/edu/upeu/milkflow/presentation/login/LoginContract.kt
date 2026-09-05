package pe.edu.upeu.milkflow.presentation.login

import pe.edu.upeu.milkflow.domain.model.Usuario

data class LoginUiState(
    val nombreUsuario: String = "",
    val clave: String = "",
    val submission: LoginSubmission = LoginSubmission.Idle,
)

sealed interface LoginSubmission {
    data object Idle : LoginSubmission
    data object Loading : LoginSubmission
    data class Success(val usuario: Usuario) : LoginSubmission
    data class Error(val message: String) : LoginSubmission
}

sealed interface LoginUiEvent {
    data class NombreUsuarioChanged(val value: String) : LoginUiEvent
    data class ClaveChanged(val value: String) : LoginUiEvent
    data object IniciarSesion : LoginUiEvent
}
