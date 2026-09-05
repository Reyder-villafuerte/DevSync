package pe.edu.upeu.milkflow.presentation.acopiador

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlin.uuid.Uuid
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.AccesoDenegadoException
import pe.edu.upeu.milkflow.domain.model.AccionUsuario
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAcopiador
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class AcopiadorViewModel(
    private val acopiadorRepository: AcopiadorRepository,
    private val registrarAcopiador: RegistrarAcopiador,
    private val sesionUsuario: SesionUsuario,
    private val validarPermisoUsuario: ValidarPermisoUsuario,
    private val registrarAuditoria: RegistrarAuditoria,
    private val ahora: () -> kotlin.time.Instant = { Clock.System.now() },
    private val idGenerator: () -> String = { Uuid.random().toString() },
    private val auditIdGenerator: () -> String = { Uuid.random().toString() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(AcopiadorUiState())
    val uiState: StateFlow<AcopiadorUiState> = _uiState.asStateFlow()

    init {
        cargarAcopiadores()
    }

    fun onEvent(event: AcopiadorUiEvent) {
        when (event) {
            AcopiadorUiEvent.Load, AcopiadorUiEvent.Retry -> cargarAcopiadores()
            is AcopiadorUiEvent.NombreChanged -> _uiState.update {
                it.copy(nombre = event.value, registration = AcopiadorRegistrationState.Idle)
            }
            AcopiadorUiEvent.Register -> registrar()
            AcopiadorUiEvent.ClearRegistrationMessage -> _uiState.update {
                it.copy(registration = AcopiadorRegistrationState.Idle)
            }
        }
    }

    private fun cargarAcopiadores() {
        _uiState.update { it.copy(content = AcopiadorContentState.Loading) }
        viewModelScope.launch {
            try {
                val acopiadores = acopiadorRepository.obtenerTodos().sortedBy(Acopiador::nombre)
                _uiState.update {
                    it.copy(
                        content = if (acopiadores.isEmpty()) {
                            AcopiadorContentState.Empty
                        } else {
                            AcopiadorContentState.Success(acopiadores)
                        },
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(
                        content = AcopiadorContentState.Error(
                            "No se pudieron cargar los acopiadores.",
                        ),
                    )
                }
            }
        }
    }

    private fun registrar() {
        val state = _uiState.value
        if (state.registration == AcopiadorRegistrationState.Saving) return
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            _uiState.update {
                it.copy(
                    registration = AcopiadorRegistrationState.Error(
                        "Inicia sesión para registrar acopiadores.",
                    ),
                )
            }
            return
        }
        val nombre = state.nombre.trim()
        if (nombre.isBlank()) {
            _uiState.update {
                it.copy(
                    registration = AcopiadorRegistrationState.Error(
                        "Ingresa el nombre del acopiador.",
                    ),
                )
            }
            return
        }

        _uiState.update { it.copy(registration = AcopiadorRegistrationState.Saving) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(usuario.rol, AccionUsuario.REGISTRAR_ACOPIADOR)
                val registrado = registrarAcopiador(Acopiador(idGenerator(), nombre))
                registrarAuditoria(
                    id = auditIdGenerator(),
                    usuarioId = usuario.id,
                    fechaHora = ahora(),
                    registroAfectadoId = registrado.id,
                    accion = "REGISTRAR_ACOPIADOR",
                )
                val actuales = when (val content = _uiState.value.content) {
                    is AcopiadorContentState.Success -> content.acopiadores
                    else -> emptyList()
                }
                _uiState.update {
                    it.copy(
                        content = AcopiadorContentState.Success(
                            (actuales + registrado).distinctBy(Acopiador::id).sortedBy(Acopiador::nombre),
                        ),
                        nombre = "",
                        registration = AcopiadorRegistrationState.Success(
                            "Acopiador registrado correctamente.",
                        ),
                    )
                }
            } catch (_: AccesoDenegadoException) {
                _uiState.update {
                    it.copy(
                        registration = AcopiadorRegistrationState.Error(
                            "Tu rol no tiene permiso para registrar acopiadores.",
                        ),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(
                        registration = AcopiadorRegistrationState.Error(
                            "No se pudo registrar el acopiador.",
                        ),
                    )
                }
            }
        }
    }
}
