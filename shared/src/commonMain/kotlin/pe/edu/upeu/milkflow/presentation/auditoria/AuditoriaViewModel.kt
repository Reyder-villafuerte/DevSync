package pe.edu.upeu.milkflow.presentation.auditoria

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.model.AccionUsuario
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class AuditoriaViewModel(
    private val sesionUsuario: SesionUsuario,
    private val auditoriaRepository: AuditoriaRepository,
    private val validarPermisoUsuario: ValidarPermisoUsuario,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AuditoriaUiState())
    val uiState: StateFlow<AuditoriaUiState> = _uiState.asStateFlow()

    private var auditoriaJob: Job? = null

    init {
        cargar()
    }

    fun onEvent(event: AuditoriaUiEvent) {
        when (event) {
            AuditoriaUiEvent.Load, AuditoriaUiEvent.Retry -> cargar()
        }
    }

    private fun cargar() {
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            _uiState.update {
                it.copy(content = AuditoriaContentState.Error("No hay una sesión activa."))
            }
            return
        }
        if (!validarPermisoUsuario(usuario.rol, AccionUsuario.CONSULTAR_AUDITORIA)) {
            _uiState.update {
                it.copy(
                    content = AuditoriaContentState.Error(
                        "Tu rol no tiene permiso para consultar auditoría.",
                    ),
                )
            }
            return
        }
        auditoriaJob?.cancel()
        _uiState.update { it.copy(content = AuditoriaContentState.Loading) }
        auditoriaJob = viewModelScope.launch {
            try {
                auditoriaRepository.observarTodos().collect { registros ->
                    _uiState.update {
                        it.copy(
                            content = if (registros.isEmpty()) {
                                AuditoriaContentState.Empty
                            } else {
                                AuditoriaContentState.Success(
                                    registros.map { registro ->
                                        AuditoriaUiItem(
                                            id = registro.id,
                                            usuarioId = registro.usuarioId,
                                            fechaHora = registro.fechaHora.toString(),
                                            registroAfectadoId = registro.registroAfectadoId,
                                            accion = registro.accion,
                                        )
                                    },
                                )
                            },
                        )
                    }
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(content = AuditoriaContentState.Error("No se pudo consultar la auditoría."))
                }
            }
        }
    }
}
