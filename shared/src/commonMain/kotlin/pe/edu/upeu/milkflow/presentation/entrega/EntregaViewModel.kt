package pe.edu.upeu.milkflow.presentation.entrega

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlin.time.Instant
import kotlin.uuid.Uuid
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.AccesoDenegadoException
import pe.edu.upeu.milkflow.domain.AcopiadorNoEncontradoException
import pe.edu.upeu.milkflow.domain.EntregaInvalidaException
import pe.edu.upeu.milkflow.domain.LitrosInvalidosException
import pe.edu.upeu.milkflow.domain.ProductorInactivoException
import pe.edu.upeu.milkflow.domain.ProductorNoEncontradoException
import pe.edu.upeu.milkflow.domain.model.AccionUsuario
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregas
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarEntregaDirecta
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLecheRecogida
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class EntregaViewModel(
    private val obtenerEntregas: ObtenerEntregas,
    private val productorRepository: ProductorRepository,
    private val acopiadorRepository: AcopiadorRepository,
    private val registrarEntregaDirecta: RegistrarEntregaDirecta,
    private val registrarLecheRecogida: RegistrarLecheRecogida,
    private val sesionUsuario: SesionUsuario,
    private val validarPermisoUsuario: ValidarPermisoUsuario,
    private val registrarAuditoria: RegistrarAuditoria,
    private val ahora: () -> Instant = { Clock.System.now() },
    private val idGenerator: () -> String = { Uuid.random().toString() },
    private val auditIdGenerator: () -> String = { Uuid.random().toString() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(EntregaUiState())
    val uiState: StateFlow<EntregaUiState> = _uiState.asStateFlow()

    init {
        cargarDatos()
        configurarSegunRol()
    }

    fun onEvent(event: EntregaUiEvent) {
        when (event) {
            EntregaUiEvent.Load, EntregaUiEvent.Retry -> {
                cargarDatos()
                configurarSegunRol()
            }
            is EntregaUiEvent.SelectProductor -> _uiState.update {
                it.copy(productorId = event.productorId, submission = EntregaSubmissionState.Idle)
            }
            is EntregaUiEvent.LitrosChanged -> _uiState.update {
                it.copy(litros = event.value, submission = EntregaSubmissionState.Idle)
            }
            is EntregaUiEvent.TipoChanged -> _uiState.update {
                it.copy(
                    tipo = event.value,
                    acopiadorId = if (event.value == TipoEntrega.DIRECTA) null else it.acopiadorId,
                    sector = if (event.value == TipoEntrega.DIRECTA) "" else it.sector,
                    submission = EntregaSubmissionState.Idle,
                )
            }
            is EntregaUiEvent.SelectAcopiador -> _uiState.update {
                it.copy(acopiadorId = event.acopiadorId, submission = EntregaSubmissionState.Idle)
            }
            is EntregaUiEvent.SectorChanged -> _uiState.update {
                it.copy(sector = event.value, submission = EntregaSubmissionState.Idle)
            }
            EntregaUiEvent.Save -> guardarEntrega()
            EntregaUiEvent.ClearSubmissionMessage -> _uiState.update {
                it.copy(submission = EntregaSubmissionState.Idle)
            }
        }
    }

    private fun cargarDatos() {
        _uiState.update { it.copy(content = EntregaContentState.Loading) }
        viewModelScope.launch {
            try {
                val productores = productorRepository.obtenerTodos().sortedBy(Productor::nombre)
                val acopiadores = acopiadorRepository.obtenerTodos().sortedBy(Acopiador::nombre)
                val entregas = obtenerEntregas().sortedByDescending(Entrega::fechaHora)
                val items = entregas.map { it.toUiItem(productores, acopiadores) }
                _uiState.update {
                    it.copy(
                        productores = productores,
                        acopiadores = acopiadores,
                        content = if (items.isEmpty()) {
                            EntregaContentState.Empty
                        } else {
                            EntregaContentState.Success(items)
                        },
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(
                        content = EntregaContentState.Error(
                            "No se pudieron cargar las entregas.",
                        ),
                    )
                }
            }
        }
    }

    private fun guardarEntrega() {
        val state = _uiState.value
        if (state.submission == EntregaSubmissionState.Saving) return
        val validationMessage = state.validationMessage(sesionUsuario.usuario.value?.id)
        if (validationMessage != null) {
            _uiState.update {
                it.copy(submission = EntregaSubmissionState.Error(validationMessage))
            }
            return
        }

        val productorId = state.productorId ?: return
        val litros = state.litros.toDoubleOrNull() ?: return
        val usuario = sesionUsuario.usuario.value ?: return
        val accion = if (state.tipo == TipoEntrega.DIRECTA) {
            AccionUsuario.REGISTRAR_ENTREGA_DIRECTA
        } else {
            AccionUsuario.REGISTRAR_LECHE_RECOGIDA
        }
        _uiState.update { it.copy(submission = EntregaSubmissionState.Saving) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(usuario.rol, accion)
                val entrega = when (state.tipo) {
                    TipoEntrega.DIRECTA -> registrarEntregaDirecta(
                        id = idGenerator(),
                        productorId = productorId,
                        fechaHora = ahora(),
                        litros = litros,
                        usuarioRegistroId = usuario.id,
                    )
                    TipoEntrega.RECOGIDA -> registrarLecheRecogida(
                        id = idGenerator(),
                        productorId = productorId,
                        fechaHora = ahora(),
                        litros = litros,
                        usuarioRegistroId = usuario.id,
                        acopiadorId = state.acopiadorId.orEmpty(),
                        sector = state.sector.trim(),
                    )
                }
                registrarAuditoria(
                    id = auditIdGenerator(),
                    usuarioId = usuario.id,
                    fechaHora = ahora(),
                    registroAfectadoId = entrega.id,
                    accion = if (entrega.tipo == TipoEntrega.DIRECTA) {
                        "REGISTRAR_ENTREGA_DIRECTA"
                    } else {
                        "REGISTRAR_LECHE_RECOGIDA"
                    },
                )
                agregarEntregaRegistrada(entrega)
            } catch (_: AccesoDenegadoException) {
                mostrarError("Tu rol no tiene permiso para registrar este tipo de entrega.")
            } catch (error: CancellationException) {
                throw error
            } catch (_: ProductorInactivoException) {
                mostrarError("El productor seleccionado está inactivo.")
            } catch (_: ProductorNoEncontradoException) {
                mostrarError("El productor seleccionado no está registrado.")
            } catch (_: LitrosInvalidosException) {
                mostrarError("Los litros deben ser mayores que cero.")
            } catch (_: AcopiadorNoEncontradoException) {
                mostrarError("El acopiador seleccionado no está registrado.")
            } catch (_: EntregaInvalidaException) {
                mostrarError("Los datos de la entrega no son válidos.")
            } catch (_: Exception) {
                mostrarError("No se pudo guardar la entrega.")
            }
        }
    }

    private fun agregarEntregaRegistrada(entrega: Entrega) {
        _uiState.update { state ->
            val existentes = (state.content as? EntregaContentState.Success)?.entregas.orEmpty()
            state.copy(
                content = EntregaContentState.Success(
                    listOf(entrega.toUiItem(state.productores, state.acopiadores)) + existentes,
                ),
                productorId = null,
                litros = "",
                tipo = TipoEntrega.DIRECTA,
                acopiadorId = null,
                sector = "",
                submission = EntregaSubmissionState.Success(
                    "Entrega registrada correctamente y pendiente de sincronización.",
                ),
            )
        }
    }

    private fun mostrarError(message: String) {
        _uiState.update { it.copy(submission = EntregaSubmissionState.Error(message)) }
    }

    private fun configurarSegunRol() {
        val rol = sesionUsuario.usuario.value?.rol ?: return
        val tipos = when (rol) {
            pe.edu.upeu.milkflow.domain.model.RolUsuario.ACOPIADOR -> listOf(TipoEntrega.RECOGIDA)
            pe.edu.upeu.milkflow.domain.model.RolUsuario.ADMINISTRADORA -> listOf(TipoEntrega.DIRECTA, TipoEntrega.RECOGIDA)
            else -> emptyList()
        }
        val titulo = when (rol) {
            pe.edu.upeu.milkflow.domain.model.RolUsuario.ACOPIADOR -> "Nueva recolección"
            else -> "Registrar entrega"
        }
        _uiState.update { 
            it.copy(
                tiposDisponibles = tipos,
                tipo = tipos.firstOrNull() ?: TipoEntrega.DIRECTA,
                titulo = titulo
            ) 
        }
    }
}

private fun EntregaUiState.validationMessage(usuarioId: String?): String? {
    if (usuarioId == null) return "No hay un usuario autenticado para registrar la entrega."
    val productor = productorSeleccionado ?: return "Selecciona un productor."
    if (!productor.activo) return "El productor seleccionado está inactivo."
    val litrosValue = litros.toDoubleOrNull()
    if (litrosValue == null || !litrosValue.isFinite() || litrosValue <= 0.0) {
        return "Los litros deben ser mayores que cero."
    }
    if (tipo == TipoEntrega.RECOGIDA && acopiadorId == null) {
        return "Selecciona un acopiador."
    }
    if (tipo == TipoEntrega.RECOGIDA && sector.isBlank()) {
        return "Ingresa el sector de recojo."
    }
    return null
}

private fun Entrega.toUiItem(
    productores: List<Productor>,
    acopiadores: List<Acopiador>,
): EntregaUiItem = EntregaUiItem(
    id = id,
    productorNombre = productores.firstOrNull { it.id == productorId }?.nombre
        ?: "Productor no disponible",
    fechaHora = fechaHora.toString(),
    litros = litros,
    tipo = tipo,
    estadoSincronizacion = estadoSincronizacion,
    acopiadorNombre = acopiadorId?.let { id ->
        acopiadores.firstOrNull { it.id == id }?.nombre ?: "Acopiador no disponible"
    },
    sector = sector,
)
