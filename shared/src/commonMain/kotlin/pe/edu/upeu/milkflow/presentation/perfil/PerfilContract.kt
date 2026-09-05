package pe.edu.upeu.milkflow.presentation.perfil

import pe.edu.upeu.milkflow.domain.model.RolUsuario

data class PerfilUiState(
    val nombreUsuario: String = "",
    val nombre: String = "",
    val rol: RolUsuario? = null,
    val sesionActiva: Boolean = false,
    val cierreProcesado: Boolean = false,
)

sealed interface PerfilUiEvent {
    data object CerrarSesion : PerfilUiEvent
    data object CierreProcesado : PerfilUiEvent
}
