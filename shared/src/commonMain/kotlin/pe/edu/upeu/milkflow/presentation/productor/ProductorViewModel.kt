package pe.edu.upeu.milkflow.presentation.productor

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
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ActualizarProductor
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarProductor
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class ProductorViewModel(
    private val productorRepository: ProductorRepository,
    private val actualizarProductor: ActualizarProductor,
    private val registrarProductor: RegistrarProductor,
    private val sesionUsuario: SesionUsuario,
    private val validarPermisoUsuario: ValidarPermisoUsuario,
    private val registrarAuditoria: RegistrarAuditoria,
    private val ahora: () -> kotlin.time.Instant = { Clock.System.now() },
    private val idGenerator: () -> String = { Uuid.random().toString() },
    private val auditIdGenerator: () -> String = { Uuid.random().toString() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProductorUiState())
    val uiState: StateFlow<ProductorUiState> = _uiState.asStateFlow()

    init {
        cargarProductores()
        verificarPermisos()
    }

    fun onEvent(event: ProductorUiEvent) {
        when (event) {
            ProductorUiEvent.Load, ProductorUiEvent.Retry -> {
                verificarPermisos()
                cargarProductores()
            }
            is ProductorUiEvent.Select -> seleccionar(event.productorId)
            is ProductorUiEvent.NombreChanged -> _uiState.update {
                it.copy(nombreEditado = event.value, saveState = ProductorSaveState.Idle)
            }
            is ProductorUiEvent.ActivoChanged -> _uiState.update {
                it.copy(activoEditado = event.value, saveState = ProductorSaveState.Idle)
            }
            ProductorUiEvent.Nuevo -> _uiState.update {
                it.copy(
                    productorSeleccionadoId = null,
                    nombreEditado = "",
                    activoEditado = true,
                    saveState = ProductorSaveState.Idle
                )
            }
            ProductorUiEvent.Save -> guardarActualizacion()
            ProductorUiEvent.Registrar -> registrar()
            ProductorUiEvent.ClearSaveMessage -> _uiState.update {
                it.copy(saveState = ProductorSaveState.Idle)
            }
        }
    }

    private fun verificarPermisos() {
        val usuario = sesionUsuario.usuario.value
        _uiState.update {
            it.copy(
                puedeRegistrar = usuario != null &&
                    validarPermisoUsuario(usuario.rol, AccionUsuario.REGISTRAR_PRODUCTOR)
            )
        }
    }

    private fun cargarProductores() {
        _uiState.update { it.copy(content = ProductorContentState.Loading) }
        viewModelScope.launch {
            try {
                val productores = productorRepository.obtenerTodos().sortedBy(Productor::nombre)
                _uiState.update {
                    it.copy(
                        content = if (productores.isEmpty()) {
                            ProductorContentState.Empty
                        } else {
                            ProductorContentState.Success(productores)
                        },
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(
                        content = ProductorContentState.Error(
                            "No se pudieron cargar los productores.",
                        ),
                    )
                }
            }
        }
    }

    private fun seleccionar(productorId: String) {
        val productor = (_uiState.value.content as? ProductorContentState.Success)
            ?.productores
            ?.firstOrNull { it.id == productorId }
            ?: return
        _uiState.update {
            it.copy(
                productorSeleccionadoId = productor.id,
                nombreEditado = productor.nombre,
                activoEditado = productor.activo,
                saveState = ProductorSaveState.Idle,
            )
        }
    }

    private fun registrar() {
        val state = _uiState.value
        if (state.saveState == ProductorSaveState.Saving) return
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            _uiState.update {
                it.copy(saveState = ProductorSaveState.Error("Inicia sesión para registrar productores."))
            }
            return
        }
        if (state.nombreEditado.isBlank()) {
            _uiState.update {
                it.copy(saveState = ProductorSaveState.Error("Ingresa el nombre del productor."))
            }
            return
        }

        _uiState.update { it.copy(saveState = ProductorSaveState.Saving) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(usuario.rol, AccionUsuario.REGISTRAR_PRODUCTOR)
                val nuevo = registrarProductor(
                    Productor(
                        id = idGenerator(),
                        nombre = state.nombreEditado.trim(),
                        activo = true
                    )
                )
                registrarAuditoria(
                    id = auditIdGenerator(),
                    usuarioId = usuario.id,
                    fechaHora = ahora(),
                    registroAfectadoId = nuevo.id,
                    accion = "REGISTRAR_PRODUCTOR",
                )
                cargarProductores()
                _uiState.update {
                    it.copy(
                        productorSeleccionadoId = nuevo.id,
                        saveState = ProductorSaveState.Success("Productor registrado correctamente.")
                    )
                }
            } catch (_: AccesoDenegadoException) {
                _uiState.update {
                    it.copy(saveState = ProductorSaveState.Error("Tu rol no tiene permiso para registrar productores."))
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(saveState = ProductorSaveState.Error("No se pudo registrar el productor."))
                }
            }
        }
    }

    private fun guardarActualizacion() {
        val state = _uiState.value
        if (state.saveState == ProductorSaveState.Saving) return
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            _uiState.update {
                it.copy(saveState = ProductorSaveState.Error("Inicia sesión para actualizar productores."))
            }
            return
        }
        val productorId = state.productorSeleccionadoId
        if (productorId == null || state.nombreEditado.isBlank()) {
            _uiState.update {
                it.copy(saveState = ProductorSaveState.Error("Completa los datos requeridos."))
            }
            return
        }

        _uiState.update { it.copy(saveState = ProductorSaveState.Saving) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(usuario.rol, AccionUsuario.ACTUALIZAR_PRODUCTOR)
                val actualizado = actualizarProductor(
                    Productor(
                        id = productorId,
                        nombre = state.nombreEditado.trim(),
                        activo = state.activoEditado,
                    ),
                )
                registrarAuditoria(
                    id = auditIdGenerator(),
                    usuarioId = usuario.id,
                    fechaHora = ahora(),
                    registroAfectadoId = actualizado.id,
                    accion = "ACTUALIZAR_PRODUCTOR",
                )
                val actuales = (_uiState.value.content as? ProductorContentState.Success)
                    ?.productores
                    .orEmpty()
                    .map { if (it.id == actualizado.id) actualizado else it }
                    .sortedBy(Productor::nombre)
                _uiState.update {
                    it.copy(
                        content = ProductorContentState.Success(actuales),
                        nombreEditado = actualizado.nombre,
                        activoEditado = actualizado.activo,
                        saveState = ProductorSaveState.Success("Productor actualizado correctamente."),
                    )
                }
            } catch (_: AccesoDenegadoException) {
                _uiState.update {
                    it.copy(
                        saveState = ProductorSaveState.Error(
                            "Tu rol no tiene permiso para actualizar productores.",
                        ),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(
                        saveState = ProductorSaveState.Error(
                            "No se pudo actualizar el productor.",
                        ),
                    )
                }
            }
        }
    }
}
