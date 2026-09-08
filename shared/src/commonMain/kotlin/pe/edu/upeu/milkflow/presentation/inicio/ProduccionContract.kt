package pe.edu.upeu.milkflow.presentation.inicio

import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.TipoProducto

data class ProduccionUiState(
    val content: ProduccionContentState = ProduccionContentState.Loading,
    val lotes: List<LoteProduccion> = emptyList(),
    val tipoProducto: TipoProducto = TipoProducto.QUESO_PARIA_FRESCO,
    val litrosLeche: String = "",
    val moldes: String = "",
    val observaciones: String = "",
    val submission: ProduccionSubmissionState = ProduccionSubmissionState.Idle,
    val soloHoy: Boolean = false,
) {
    val formularioValido: Boolean
        get() {
            val litrosValidos = litrosLeche.toDoubleOrNull()?.let { it > 0 } ?: false
            val moldesValidos = if (tipoProducto.esQueso()) {
                moldes.toIntOrNull()?.let { it > 0 } ?: false
            } else true
            return litrosValidos && moldesValidos
        }
}

sealed interface ProduccionContentState {
    data object Loading : ProduccionContentState
    data class Success(val lotes: List<LoteProduccion>) : ProduccionContentState
    data object Empty : ProduccionContentState
    data class Error(val message: String) : ProduccionContentState
}

sealed interface ProduccionSubmissionState {
    data object Idle : ProduccionSubmissionState
    data object Saving : ProduccionSubmissionState
    data class Success(val message: String) : ProduccionSubmissionState
    data class Error(val message: String) : ProduccionSubmissionState
}

sealed interface ProduccionUiEvent {
    data object Load : ProduccionUiEvent
    data class SetSoloHoy(val value: Boolean) : ProduccionUiEvent
    data class TipoProductoChanged(val value: TipoProducto) : ProduccionUiEvent
    data class LitrosLecheChanged(val value: String) : ProduccionUiEvent
    data class MoldesChanged(val value: String) : ProduccionUiEvent
    data class ObservacionesChanged(val value: String) : ProduccionUiEvent
    data object RegistrarLote : ProduccionUiEvent
    data object ClearSubmissionMessage : ProduccionUiEvent
}
