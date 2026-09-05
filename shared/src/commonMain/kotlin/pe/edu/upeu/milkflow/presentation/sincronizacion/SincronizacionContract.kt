package pe.edu.upeu.milkflow.presentation.sincronizacion

import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion

data class SincronizacionUiState(
    val content: SincronizacionContentState = SincronizacionContentState.Loading,
    val pendientes: Int = 0,
    val puedeSincronizar: Boolean = false,
    val submission: SincronizacionSubmissionState = SincronizacionSubmissionState.Idle,
    val backendDisponible: Boolean = false,
)

sealed interface SincronizacionContentState {
    data object Loading : SincronizacionContentState
    data class Success(val registros: List<RegistroSincronizacionUi>) : SincronizacionContentState
    data object Empty : SincronizacionContentState
    data class Error(val message: String) : SincronizacionContentState
}

data class RegistroSincronizacionUi(
    val registroId: String,
    val tipoRegistro: String,
    val estado: EstadoSincronizacion,
)

sealed interface SincronizacionSubmissionState {
    data object Idle : SincronizacionSubmissionState
    data object Syncing : SincronizacionSubmissionState
    data class Success(val message: String) : SincronizacionSubmissionState
    data class Error(val message: String) : SincronizacionSubmissionState
}

sealed interface SincronizacionUiEvent {
    data object Load : SincronizacionUiEvent
    data object Retry : SincronizacionUiEvent
    data object SincronizarPendientes : SincronizacionUiEvent
    data object ClearSubmissionMessage : SincronizacionUiEvent
}
