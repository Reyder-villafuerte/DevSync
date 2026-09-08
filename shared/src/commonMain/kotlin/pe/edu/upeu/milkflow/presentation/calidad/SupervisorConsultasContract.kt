package pe.edu.upeu.milkflow.presentation.calidad

import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad

data class SupervisorConsultasUiState(
    val inspeccionesHoy: InspeccionesHoyState = InspeccionesHoyState.Loading,
    val problemasCalidad: ProblemasCalidadState = ProblemasCalidadState.Loading,
)

sealed interface InspeccionesHoyState {
    data object Loading : InspeccionesHoyState
    data class Success(val inspecciones: List<InspeccionUiItem>) : InspeccionesHoyState
    data object Empty : InspeccionesHoyState
    data class Error(val message: String) : InspeccionesHoyState
}

sealed interface ProblemasCalidadState {
    data object Loading : ProblemasCalidadState
    data class Success(val problemas: List<ProblemaLeche>) : ProblemasCalidadState
    data object Empty : ProblemasCalidadState
    data class Error(val message: String) : ProblemasCalidadState
}

data class InspeccionUiItem(
    val prueba: PruebaCalidad,
    val problemas: List<ProblemaLeche>,
    val litrosEntrega: Double,
)

sealed interface SupervisorConsultasUiEvent {
    data object RefreshInspecciones : SupervisorConsultasUiEvent
    data object RefreshProblemas : SupervisorConsultasUiEvent
}
