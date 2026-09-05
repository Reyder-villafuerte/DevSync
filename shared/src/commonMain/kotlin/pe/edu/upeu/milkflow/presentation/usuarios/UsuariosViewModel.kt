package pe.edu.upeu.milkflow.presentation.usuarios

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlin.uuid.Uuid
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.AccesoDenegadoException
import pe.edu.upeu.milkflow.domain.model.AccionUsuario
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.usecase.GestionarUsuarios
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class UsuariosViewModel(
    private val sesionUsuario: SesionUsuario,
    private val gestionarUsuarios: GestionarUsuarios,
    private val validarPermisoUsuario: ValidarPermisoUsuario,
    private val registrarAuditoria: RegistrarAuditoria,
    private val ahora: () -> kotlin.time.Instant = { Clock.System.now() },
    private val idGenerator: () -> String = { Uuid.random().toString() },
    private val auditIdGenerator: () -> String = { Uuid.random().toString() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(UsuariosUiState())
    val uiState: StateFlow<UsuariosUiState> = _uiState.asStateFlow()

    private var usuariosJob: Job? = null

    init {
        cargar()
    }

    fun onEvent(event: UsuariosUiEvent) {
        when (event) {
            UsuariosUiEvent.Load, UsuariosUiEvent.Retry -> cargar()
            is UsuariosUiEvent.SelectUsuario -> seleccionar(event.usuarioId)
            is UsuariosUiEvent.NombreUsuarioChanged -> _uiState.update {
                it.copy(nombreUsuario = event.value, saveState = UsuariosSaveState.Idle)
            }
            is UsuariosUiEvent.NombreChanged -> _uiState.update {
                it.copy(nombre = event.value, saveState = UsuariosSaveState.Idle)
            }
            is UsuariosUiEvent.RolChanged -> _uiState.update {
                it.copy(rol = event.value, saveState = UsuariosSaveState.Idle)
            }
            is UsuariosUiEvent.ActivoChanged -> _uiState.update {
                it.copy(activo = event.value, saveState = UsuariosSaveState.Idle)
            }
            UsuariosUiEvent.NuevoUsuario -> _uiState.update {
                it.copy(
                    usuarioSeleccionadoId = null,
                    nombreUsuario = "",
                    nombre = "",
                    rol = RolUsuario.PENDIENTE_ASIGNACION,
                    activo = true,
                    saveState = UsuariosSaveState.Idle,
                )
            }
            UsuariosUiEvent.Guardar -> guardar()
            UsuariosUiEvent.LimpiarMensaje -> _uiState.update { it.copy(saveState = UsuariosSaveState.Idle) }
        }
    }

    private fun cargar() {
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            _uiState.update {
                it.copy(
                    puedeGestionar = false,
                    content = UsuariosContentState.Error("No hay una sesión activa."),
                )
            }
            return
        }

        val autorizado = validarPermisoUsuario(usuario.rol, AccionUsuario.GESTIONAR_USUARIOS)
        _uiState.update { it.copy(puedeGestionar = autorizado) }
        if (!autorizado) {
            _uiState.update {
                it.copy(
                    content = UsuariosContentState.Error(
                        "Tu rol no tiene permiso para gestionar usuarios.",
                    ),
                )
            }
            return
        }

        usuariosJob?.cancel()
        _uiState.update { it.copy(content = UsuariosContentState.Loading) }
        usuariosJob = viewModelScope.launch {
            try {
                gestionarUsuarios.observarTodos().collect { usuarios ->
                    _uiState.update { state ->
                        state.copy(
                            content = if (usuarios.isEmpty()) {
                                UsuariosContentState.Empty
                            } else {
                                UsuariosContentState.Success(usuarios.sortedBy(Usuario::nombre))
                            },
                        )
                    }
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(content = UsuariosContentState.Error("No se pudieron cargar los usuarios."))
                }
            }
        }
    }

    private fun seleccionar(usuarioId: String) {
        val usuario = (_uiState.value.content as? UsuariosContentState.Success)
            ?.usuarios
            ?.firstOrNull { it.id == usuarioId }
            ?: return
        _uiState.update {
            it.copy(
                usuarioSeleccionadoId = usuario.id,
                nombreUsuario = usuario.nombreUsuario,
                nombre = usuario.nombre,
                rol = usuario.rol,
                activo = usuario.activo,
                saveState = UsuariosSaveState.Idle,
            )
        }
    }

    private fun guardar() {
        val state = _uiState.value
        val actor = sesionUsuario.usuario.value
        if (actor == null) {
            _uiState.update {
                it.copy(saveState = UsuariosSaveState.Error("No hay una sesión activa."))
            }
            return
        }
        if (!state.formularioValido) {
            _uiState.update {
                it.copy(saveState = UsuariosSaveState.Error("Completa usuario y nombre."))
            }
            return
        }
        if (state.saveState == UsuariosSaveState.Saving) return

        _uiState.update { it.copy(saveState = UsuariosSaveState.Saving) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(actor.rol, AccionUsuario.GESTIONAR_USUARIOS)
                val usuario = Usuario(
                    id = state.usuarioSeleccionadoId ?: idGenerator(),
                    nombreUsuario = state.nombreUsuario.trim(),
                    nombre = state.nombre.trim(),
                    rol = state.rol,
                    activo = state.activo,
                )
                val accion = if (state.usuarioSeleccionadoId == null) {
                    "REGISTRAR_USUARIO"
                } else {
                    "ACTUALIZAR_USUARIO"
                }
                if (state.usuarioSeleccionadoId == null) {
                    gestionarUsuarios.registrar(usuario)
                } else {
                    gestionarUsuarios.actualizar(usuario)
                }
                registrarAuditoria(
                    id = auditIdGenerator(),
                    usuarioId = actor.id,
                    fechaHora = ahora(),
                    registroAfectadoId = usuario.id,
                    accion = accion,
                )
                _uiState.update {
                    it.copy(
                        usuarioSeleccionadoId = usuario.id,
                        nombreUsuario = usuario.nombreUsuario,
                        nombre = usuario.nombre,
                        rol = usuario.rol,
                        activo = usuario.activo,
                        saveState = UsuariosSaveState.Success("Usuario guardado correctamente."),
                    )
                }
            } catch (_: AccesoDenegadoException) {
                _uiState.update {
                    it.copy(
                        saveState = UsuariosSaveState.Error(
                            "Tu rol no tiene permiso para gestionar usuarios.",
                        ),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(saveState = UsuariosSaveState.Error("No se pudo guardar el usuario."))
                }
            }
        }
    }
}
