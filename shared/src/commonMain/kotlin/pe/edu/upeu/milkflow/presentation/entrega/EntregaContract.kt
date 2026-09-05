package pe.edu.upeu.milkflow.presentation.entrega

import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.TipoEntrega

data class EntregaUiState(
    val content: EntregaContentState = EntregaContentState.Loading,
    val productores: List<Productor> = emptyList(),
    val acopiadores: List<Acopiador> = emptyList(),
    val productorId: String? = null,
    val litros: String = "",
    val tipo: TipoEntrega = TipoEntrega.DIRECTA,
    val acopiadorId: String? = null,
    val sector: String = "",
    val submission: EntregaSubmissionState = EntregaSubmissionState.Idle,
) {
    val productorSeleccionado: Productor?
        get() = productores.firstOrNull { it.id == productorId }

    val formularioValido: Boolean
        get() {
            val litrosValidos = litros.toDoubleOrNull()?.let { it.isFinite() && it > 0.0 } == true
            val productorValido = productorSeleccionado?.activo == true
            val datosRecogidaValidos = tipo == TipoEntrega.DIRECTA ||
                (acopiadorId != null && sector.isNotBlank())
            return productorValido && litrosValidos && datosRecogidaValidos
        }
}

sealed interface EntregaContentState {
    data object Loading : EntregaContentState
    data class Success(val entregas: List<EntregaUiItem>) : EntregaContentState
    data object Empty : EntregaContentState
    data class Error(val message: String) : EntregaContentState
}

data class EntregaUiItem(
    val id: String,
    val productorNombre: String,
    val fechaHora: String,
    val litros: Double,
    val tipo: TipoEntrega,
    val estadoSincronizacion: EstadoSincronizacion,
    val acopiadorNombre: String? = null,
    val sector: String? = null,
)

sealed interface EntregaSubmissionState {
    data object Idle : EntregaSubmissionState
    data object Saving : EntregaSubmissionState
    data class Success(val message: String) : EntregaSubmissionState
    data class Error(val message: String) : EntregaSubmissionState
}

sealed interface EntregaUiEvent {
    data object Load : EntregaUiEvent
    data object Retry : EntregaUiEvent
    data class SelectProductor(val productorId: String) : EntregaUiEvent
    data class LitrosChanged(val value: String) : EntregaUiEvent
    data class TipoChanged(val value: TipoEntrega) : EntregaUiEvent
    data class SelectAcopiador(val acopiadorId: String) : EntregaUiEvent
    data class SectorChanged(val value: String) : EntregaUiEvent
    data object Save : EntregaUiEvent
    data object ClearSubmissionMessage : EntregaUiEvent
}
