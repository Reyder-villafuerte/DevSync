package pe.edu.upeu.milkflow.presentation.calidad

import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.TipoEntrega

data class CalidadUiState(
    val content: CalidadContentState = CalidadContentState.Loading,
    val entregaSeleccionadaId: String? = null,
    val entregaSeleccionada: CalidadEntregaUi? = null,
    val detalle: CalidadDetalleState = CalidadDetalleState.None,
    val observacion: String = "",
    val submission: CalidadSubmissionState = CalidadSubmissionState.Idle,
)

sealed interface CalidadContentState {
    data object Loading : CalidadContentState
    data class Success(val entregas: List<CalidadEntregaUi>) : CalidadContentState
    data object Empty : CalidadContentState
    data class Error(val message: String) : CalidadContentState
}

data class CalidadEntregaUi(
    val id: String,
    val fechaHora: String,
    val litros: Double,
    val tipo: TipoEntrega,
    val estadoSincronizacion: EstadoSincronizacion,
)

sealed interface CalidadDetalleState {
    data object None : CalidadDetalleState
    data object Loading : CalidadDetalleState
    data class Loaded(
        val prueba: PruebaCalidad?,
        val problemas: List<ProblemaLeche>,
    ) : CalidadDetalleState
    data class Error(val message: String) : CalidadDetalleState
}

sealed interface CalidadSubmissionState {
    data object Idle : CalidadSubmissionState
    data object Saving : CalidadSubmissionState
    data class Success(val message: String) : CalidadSubmissionState
    data class Error(val message: String) : CalidadSubmissionState
}

sealed interface CalidadUiEvent {
    data object Load : CalidadUiEvent
    data object Retry : CalidadUiEvent
    data class SelectEntrega(val entregaId: String) : CalidadUiEvent
    data class ObservacionChanged(val value: String) : CalidadUiEvent
    data object RegistrarPrueba : CalidadUiEvent
    data object RegistrarProblema : CalidadUiEvent
    data object ClearSubmissionMessage : CalidadUiEvent
}
