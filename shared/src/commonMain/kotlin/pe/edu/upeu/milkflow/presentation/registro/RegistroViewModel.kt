package pe.edu.upeu.milkflow.presentation.registro

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.uuid.Uuid
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.usecase.RegistrarCuenta

class RegistroViewModel(
    private val registrarCuenta: RegistrarCuenta,
    private val idGenerator: () -> String = { Uuid.random().toString() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(RegistroUiState())
    val uiState: StateFlow<RegistroUiState> = _uiState.asStateFlow()

    fun onEvent(event: RegistroUiEvent) {
        when (event) {
            is RegistroUiEvent.NombreCompletoChanged -> _uiState.update {
                it.copy(nombreCompleto = event.value, submission = RegistroSubmission.Idle)
            }
            is RegistroUiEvent.CorreoChanged -> _uiState.update {
                it.copy(correo = event.value, submission = RegistroSubmission.Idle)
            }
            is RegistroUiEvent.ClaveChanged -> _uiState.update {
                it.copy(clave = event.value, submission = RegistroSubmission.Idle)
            }
            is RegistroUiEvent.ConfirmarClaveChanged -> _uiState.update {
                it.copy(confirmarClave = event.value, submission = RegistroSubmission.Idle)
            }
            RegistroUiEvent.Registrar -> registrar()
            RegistroUiEvent.ClearMessage -> _uiState.update {
                it.copy(submission = RegistroSubmission.Idle)
            }
        }
    }

    private fun registrar() {
        val state = _uiState.value
        if (state.submission == RegistroSubmission.Loading) return

        if (state.nombreCompleto.isBlank()) {
            mostrarError("El nombre completo es obligatorio.")
            return
        }
        if (state.correo.isBlank()) {
            mostrarError("El correo es obligatorio.")
            return
        }
        if (state.clave.isBlank()) {
            mostrarError("La contraseña es obligatoria.")
            return
        }
        if (state.clave != state.confirmarClave) {
            mostrarError("Las contraseñas no coinciden.")
            return
        }

        _uiState.update { it.copy(submission = RegistroSubmission.Loading) }
        viewModelScope.launch {
            try {
                registrarCuenta(
                    id = idGenerator(),
                    nombreCompleto = state.nombreCompleto.trim(),
                    correo = state.correo.trim(),
                    clave = state.clave
                )
                _uiState.update { it.copy(submission = RegistroSubmission.Success) }
            } catch (error: CancellationException) {
                throw error
            } catch (error: Exception) {
                error.printStackTrace() // Ver en Logcat si es posible
                mostrarError("No se pudo crear la cuenta: ${error.message ?: "Error desconocido"}")
            }
        }
    }

    private fun mostrarError(message: String) {
        _uiState.update { it.copy(submission = RegistroSubmission.Error(message)) }
    }
}
