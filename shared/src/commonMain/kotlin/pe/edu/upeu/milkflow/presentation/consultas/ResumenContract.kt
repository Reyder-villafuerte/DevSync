package pe.edu.upeu.milkflow.presentation.consultas

import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.ResumenProductor

enum class ResumenPeriodo { SEMANAL, MENSUAL }

data class ResumenUiState(
    val productores: List<Productor> = emptyList(),
    val productorId: String? = null,
    val fechaReferencia: String = "",
    val periodo: ResumenPeriodo = ResumenPeriodo.SEMANAL,
    val content: ResumenContentState = ResumenContentState.Loading,
)

sealed interface ResumenContentState {
    data object Loading : ResumenContentState
    data class Success(val resumen: ResumenProductor) : ResumenContentState
    data object Empty : ResumenContentState
    data class Error(val message: String) : ResumenContentState
}

sealed interface ResumenUiEvent {
    data object Load : ResumenUiEvent
    data object Retry : ResumenUiEvent
    data class SelectProductor(val productorId: String) : ResumenUiEvent
    data class FechaChanged(val value: String) : ResumenUiEvent
    data class PeriodoChanged(val value: ResumenPeriodo) : ResumenUiEvent
    data object Consultar : ResumenUiEvent
}
