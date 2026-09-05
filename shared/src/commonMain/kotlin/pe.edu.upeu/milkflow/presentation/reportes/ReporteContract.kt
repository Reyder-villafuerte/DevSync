package pe.edu.upeu.milkflow.presentation.reportes

import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.domain.model.ReporteLeche

enum class ReportePeriodo { DIARIO, SEMANAL, MENSUAL }

data class ReporteUiState(
    val fechaReferencia: String = "",
    val periodo: ReportePeriodo = ReportePeriodo.DIARIO,
    val content: ReporteContentState = ReporteContentState.Loading,
) {
    val formularioValido: Boolean
        get() = fechaReferencia.parseDateOrNull() != null
}

sealed interface ReporteContentState {
    data object Loading : ReporteContentState
    data class Success(val reporte: ReporteLeche) : ReporteContentState
    data object Empty : ReporteContentState
    data class Error(val message: String) : ReporteContentState
}

sealed interface ReporteUiEvent {
    data object Load : ReporteUiEvent
    data object Retry : ReporteUiEvent
    data class FechaChanged(val value: String) : ReporteUiEvent
    data class PeriodoChanged(val value: ReportePeriodo) : ReporteUiEvent
    data object Consultar : ReporteUiEvent
}

internal fun String.parseDateOrNull(): LocalDate? = runCatching { LocalDate.parse(trim()) }.getOrNull()
