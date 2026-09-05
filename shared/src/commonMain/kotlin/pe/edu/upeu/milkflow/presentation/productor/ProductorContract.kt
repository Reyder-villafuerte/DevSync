package pe.edu.upeu.milkflow.presentation.productor

import pe.edu.upeu.milkflow.domain.model.Productor

data class ProductorUiState(
    val content: ProductorContentState = ProductorContentState.Loading,
    val productorSeleccionadoId: String? = null,
    val nombreEditado: String = "",
    val activoEditado: Boolean = true,
    val saveState: ProductorSaveState = ProductorSaveState.Idle,
    val puedeRegistrar: Boolean = false,
) {
    val formularioValido: Boolean
        get() = nombreEditado.isNotBlank()
}

sealed interface ProductorContentState {
    data object Loading : ProductorContentState
    data class Success(val productores: List<Productor>) : ProductorContentState
    data object Empty : ProductorContentState
    data class Error(val message: String) : ProductorContentState
}

sealed interface ProductorSaveState {
    data object Idle : ProductorSaveState
    data object Saving : ProductorSaveState
    data class Success(val message: String) : ProductorSaveState
    data class Error(val message: String) : ProductorSaveState
}

sealed interface ProductorUiEvent {
    data object Load : ProductorUiEvent
    data object Retry : ProductorUiEvent
    data class Select(val productorId: String) : ProductorUiEvent
    data class NombreChanged(val value: String) : ProductorUiEvent
    data class ActivoChanged(val value: Boolean) : ProductorUiEvent
    data object Nuevo : ProductorUiEvent
    data object Save : ProductorUiEvent
    data object Registrar : ProductorUiEvent
    data object ClearSaveMessage : ProductorUiEvent
}
