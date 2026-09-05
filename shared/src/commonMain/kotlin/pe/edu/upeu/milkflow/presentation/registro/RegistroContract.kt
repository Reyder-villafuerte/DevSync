package pe.edu.upeu.milkflow.presentation.registro

data class RegistroUiState(
    val nombreCompleto: String = "",
    val correo: String = "",
    val clave: String = "",
    val confirmarClave: String = "",
    val submission: RegistroSubmission = RegistroSubmission.Idle,
) {
    val formularioValido: Boolean
        get() = nombreCompleto.isNotBlank() &&
                correo.isNotBlank() &&
                clave.isNotBlank() &&
                confirmarClave.isNotBlank() &&
                clave == confirmarClave
}

sealed interface RegistroSubmission {
    data object Idle : RegistroSubmission
    data object Loading : RegistroSubmission
    data object Success : RegistroSubmission
    data class Error(val message: String) : RegistroSubmission
}

sealed interface RegistroUiEvent {
    data class NombreCompletoChanged(val value: String) : RegistroUiEvent
    data class CorreoChanged(val value: String) : RegistroUiEvent
    data class ClaveChanged(val value: String) : RegistroUiEvent
    data class ConfirmarClaveChanged(val value: String) : RegistroUiEvent
    data object Registrar : RegistroUiEvent
    data object ClearMessage : RegistroUiEvent
}
