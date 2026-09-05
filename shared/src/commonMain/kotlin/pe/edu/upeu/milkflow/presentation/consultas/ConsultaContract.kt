package pe.edu.upeu.milkflow.presentation.consultas

import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.TipoEntrega

data class ConsultaUiState(
    val productores: List<Productor> = emptyList(),
    val productorId: String? = null,
    val fechaInicio: String = "",
    val fechaFin: String = "",
    val content: ConsultaContentState = ConsultaContentState.Loading,
) {
    val rangoValido: Boolean
        get() = fechaInicio.parseDateOrNull()?.let { inicio ->
            fechaFin.parseDateOrNull()?.let { fin -> inicio <= fin }
        } == true
}

sealed interface ConsultaContentState {
    data object Loading : ConsultaContentState
    data class Success(val entregas: List<ConsultaEntregaUi>) : ConsultaContentState
    data object Empty : ConsultaContentState
    data class Error(val message: String) : ConsultaContentState
}

data class ConsultaEntregaUi(
    val id: String,
    val fechaHora: String,
    val litros: Double,
    val tipo: TipoEntrega,
    val estadoSincronizacion: EstadoSincronizacion,
    val acopiadorId: String? = null,
    val sector: String? = null,
)

sealed interface ConsultaUiEvent {
    data object Load : ConsultaUiEvent
    data object Retry : ConsultaUiEvent
    data class SelectProductor(val productorId: String) : ConsultaUiEvent
    data class FechaInicioChanged(val value: String) : ConsultaUiEvent
    data class FechaFinChanged(val value: String) : ConsultaUiEvent
    data object Buscar : ConsultaUiEvent
}
