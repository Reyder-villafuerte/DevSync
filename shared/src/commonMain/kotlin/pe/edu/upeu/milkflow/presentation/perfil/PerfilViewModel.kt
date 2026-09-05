package pe.edu.upeu.milkflow.presentation.perfil

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class PerfilViewModel(
    private val sesionUsuario: SesionUsuario,
) : ViewModel() {
    private val _uiState = MutableStateFlow(PerfilUiState())
    val uiState: StateFlow<PerfilUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            sesionUsuario.usuario.collect { usuario ->
                _uiState.update {
                    it.copy(
                        nombreUsuario = usuario?.nombreUsuario.orEmpty(),
                        nombre = usuario?.nombre.orEmpty(),
                        rol = usuario?.rol,
                        sesionActiva = usuario != null,
                    )
                }
            }
        }
    }

    fun onEvent(event: PerfilUiEvent) {
        when (event) {
            PerfilUiEvent.CerrarSesion -> {
                sesionUsuario.cerrar()
                _uiState.update { it.copy(cierreProcesado = false) }
            }
            PerfilUiEvent.CierreProcesado -> _uiState.update { it.copy(cierreProcesado = true) }
        }
    }
}
