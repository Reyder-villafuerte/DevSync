package pe.edu.upeu.milkflow.presentation.sincronizacion

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.AccesoDenegadoException
import pe.edu.upeu.milkflow.domain.model.AccionUsuario
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEstadoSincronizacion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.SincronizarRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class SincronizacionViewModel(
    private val sesionUsuario: SesionUsuario,
    private val sincronizacionRepository: SincronizacionRepository,
    private val obtenerRegistrosPendientes: ObtenerRegistrosPendientes,
    private val sincronizarRegistrosPendientes: SincronizarRegistrosPendientes,
    private val obtenerEstadoSincronizacion: ObtenerEstadoSincronizacion,
    private val validarPermisoUsuario: ValidarPermisoUsuario,
) : ViewModel() {
    private val _uiState = MutableStateFlow(SincronizacionUiState())
    val uiState: StateFlow<SincronizacionUiState> = _uiState.asStateFlow()

    private var registrosJob: Job? = null
    private var pendientesJob: Job? = null

    init {
        observarRegistros()
        observarPendientes()
        actualizarPermisos()
    }

    fun onEvent(event: SincronizacionUiEvent) {
        when (event) {
            SincronizacionUiEvent.Load, SincronizacionUiEvent.Retry -> {
                observarRegistros()
                observarPendientes()
                actualizarPermisos()
            }
            SincronizacionUiEvent.SincronizarPendientes -> sincronizar()
            SincronizacionUiEvent.ClearSubmissionMessage -> _uiState.update {
                it.copy(submission = SincronizacionSubmissionState.Idle)
            }
        }
    }

    private fun observarRegistros() {
        registrosJob?.cancel()
        _uiState.update { it.copy(content = SincronizacionContentState.Loading) }
        registrosJob = viewModelScope.launch {
            try {
                sincronizacionRepository.observarRegistros().collect { registros ->
                    _uiState.update {
                        it.copy(
                            content = if (registros.isEmpty()) {
                                SincronizacionContentState.Empty
                            } else {
                                SincronizacionContentState.Success(
                                    registros.map { registro ->
                                        RegistroSincronizacionUi(
                                            registroId = registro.registroId,
                                            tipoRegistro = registro.tipoRegistro,
                                            estado = registro.estado,
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
                    it.copy(
                        content = SincronizacionContentState.Error(
                            "No se pudo cargar el estado de sincronización.",
                        ),
                    )
                }
            }
        }
    }

    private fun observarPendientes() {
        pendientesJob?.cancel()
        pendientesJob = viewModelScope.launch {
            try {
                obtenerRegistrosPendientes().collect { pendientes ->
                    _uiState.update { it.copy(pendientes = pendientes.size) }
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update { it.copy(pendientes = 0) }
            }
        }
    }

    private fun actualizarPermisos() {
        val usuario = sesionUsuario.usuario.value
        _uiState.update {
            it.copy(
                puedeSincronizar = usuario != null &&
                    validarPermisoUsuario(usuario.rol, AccionUsuario.SINCRONIZAR_REGISTROS),
            )
        }
    }

    private fun sincronizar() {
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            _uiState.update {
                it.copy(
                    submission = SincronizacionSubmissionState.Error(
                        "Inicia sesión para sincronizar registros.",
                    ),
                )
            }
            return
        }
        if (_uiState.value.submission == SincronizacionSubmissionState.Syncing) return

        _uiState.update { it.copy(submission = SincronizacionSubmissionState.Syncing) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(usuario.rol, AccionUsuario.SINCRONIZAR_REGISTROS)
                val pendientesAntes = sincronizacionRepository.obtenerPendientes()
                val resultado = sincronizarRegistrosPendientes()
                val estadosFinales = pendientesAntes.mapNotNull { registro ->
                    obtenerEstadoSincronizacion(
                        tipoRegistro = registro.tipoRegistro,
                        registroId = registro.registroId,
                    )
                }
                val enviados = estadosFinales.count { it == EstadoSincronizacion.ENVIADO }
                val errores = estadosFinales.count { it == EstadoSincronizacion.ERROR }
                _uiState.update {
                    it.copy(
                        backendDisponible = enviados > 0,
                        submission = SincronizacionSubmissionState.Success(
                            if (resultado.total == 0) {
                                "No hay registros pendientes por sincronizar."
                            } else {
                                "Sincronización finalizada: $enviados enviados y $errores con error."
                            },
                        ),
                    )
                }
            } catch (_: AccesoDenegadoException) {
                _uiState.update {
                    it.copy(
                        submission = SincronizacionSubmissionState.Error(
                            "Tu rol no tiene permiso para sincronizar.",
                        ),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(
                        submission = SincronizacionSubmissionState.Error(
                            "No se pudo completar la sincronización.",
                        ),
                    )
                }
            }
        }
    }
}
