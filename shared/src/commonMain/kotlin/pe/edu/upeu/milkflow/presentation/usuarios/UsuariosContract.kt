package pe.edu.upeu.milkflow.presentation.usuarios

import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario

data class UsuariosUiState(
    val content: UsuariosContentState = UsuariosContentState.Loading,
    val usuarioSeleccionadoId: String? = null,
    val nombreUsuario: String = "",
    val nombre: String = "",
    val rol: RolUsuario = RolUsuario.PENDIENTE_ASIGNACION,
    val activo: Boolean = true,
    val puedeGestionar: Boolean = false,
    val saveState: UsuariosSaveState = UsuariosSaveState.Idle,
) {
    val formularioValido: Boolean
        get() = nombreUsuario.isNotBlank() && nombre.isNotBlank()
}

sealed interface UsuariosContentState {
    data object Loading : UsuariosContentState
    data class Success(val usuarios: List<Usuario>) : UsuariosContentState
    data object Empty : UsuariosContentState
    data class Error(val message: String) : UsuariosContentState
}

sealed interface UsuariosSaveState {
    data object Idle : UsuariosSaveState
    data object Saving : UsuariosSaveState
    data class Success(val message: String) : UsuariosSaveState
    data class Error(val message: String) : UsuariosSaveState
}

sealed interface UsuariosUiEvent {
    data object Load : UsuariosUiEvent
    data object Retry : UsuariosUiEvent
    data class SelectUsuario(val usuarioId: String) : UsuariosUiEvent
    data class NombreUsuarioChanged(val value: String) : UsuariosUiEvent
    data class NombreChanged(val value: String) : UsuariosUiEvent
    data class RolChanged(val value: RolUsuario) : UsuariosUiEvent
    data class ActivoChanged(val value: Boolean) : UsuariosUiEvent
    data object NuevoUsuario : UsuariosUiEvent
    data object Guardar : UsuariosUiEvent
    data object LimpiarMensaje : UsuariosUiEvent
}
