package pe.edu.upeu.milkflow.presentation.auditoria

data class AuditoriaUiState(
    val content: AuditoriaContentState = AuditoriaContentState.Loading,
)

sealed interface AuditoriaContentState {
    data object Loading : AuditoriaContentState
    data class Success(val registros: List<AuditoriaUiItem>) : AuditoriaContentState
    data object Empty : AuditoriaContentState
    data class Error(val message: String) : AuditoriaContentState
}

data class AuditoriaUiItem(
    val id: String,
    val usuarioId: String,
    val fechaHora: String,
    val registroAfectadoId: String,
    val accion: String,
)

sealed interface AuditoriaUiEvent {
    data object Load : AuditoriaUiEvent
    data object Retry : AuditoriaUiEvent
}
