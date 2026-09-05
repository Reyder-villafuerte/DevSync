package pe.edu.upeu.milkflow.presentation.acopiador

import pe.edu.upeu.milkflow.domain.model.Acopiador

data class AcopiadorUiState(
    val content: AcopiadorContentState = AcopiadorContentState.Loading,
    val nombre: String = "",
    val registration: AcopiadorRegistrationState = AcopiadorRegistrationState.Idle,
) {
    val formularioValido: Boolean
        get() = nombre.isNotBlank()
}

sealed interface AcopiadorContentState {
    data object Loading : AcopiadorContentState
    data class Success(val acopiadores: List<Acopiador>) : AcopiadorContentState
    data object Empty : AcopiadorContentState
    data class Error(val message: String) : AcopiadorContentState
}

sealed interface AcopiadorRegistrationState {
    data object Idle : AcopiadorRegistrationState
    data object Saving : AcopiadorRegistrationState
    data class Success(val message: String) : AcopiadorRegistrationState
    data class Error(val message: String) : AcopiadorRegistrationState
}

sealed interface AcopiadorUiEvent {
    data object Load : AcopiadorUiEvent
    data object Retry : AcopiadorUiEvent
    data class NombreChanged(val value: String) : AcopiadorUiEvent
    data object Register : AcopiadorUiEvent
    data object ClearRegistrationMessage : AcopiadorUiEvent
}
