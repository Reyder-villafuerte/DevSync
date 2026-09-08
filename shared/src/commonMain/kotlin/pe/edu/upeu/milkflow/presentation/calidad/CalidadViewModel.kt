package pe.edu.upeu.milkflow.presentation.calidad

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
import pe.edu.upeu.milkflow.domain.AcopiadorNoEncontradoException
import pe.edu.upeu.milkflow.domain.EntregaNoEncontradaException
import pe.edu.upeu.milkflow.domain.PruebaCalidadInvalidaException
import pe.edu.upeu.milkflow.domain.model.AccionUsuario
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregas
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarProblemaLeche
import pe.edu.upeu.milkflow.domain.usecase.RegistrarPruebaCalidad
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class CalidadViewModel(
    private val obtenerEntregas: ObtenerEntregas,
    private val calidadRepository: CalidadRepository,
    private val registrarPruebaCalidad: RegistrarPruebaCalidad,
    private val registrarProblemaLeche: RegistrarProblemaLeche,
    private val sesionUsuario: SesionUsuario,
    private val validarPermisoUsuario: ValidarPermisoUsuario,
    private val registrarAuditoria: RegistrarAuditoria,
    private val ahora: () -> kotlin.time.Instant = { Clock.System.now() },
    private val idGenerator: () -> String = { Uuid.random().toString() },
    private val auditIdGenerator: () -> String = { Uuid.random().toString() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(CalidadUiState())
    val uiState: StateFlow<CalidadUiState> = _uiState.asStateFlow()

    init {
        cargarEntregas()
    }

    fun onEvent(event: CalidadUiEvent) {
        when (event) {
            CalidadUiEvent.Load, CalidadUiEvent.Retry -> cargarEntregas()
            is CalidadUiEvent.SelectEntrega -> seleccionarEntrega(event.entregaId)
            is CalidadUiEvent.ObservacionChanged -> _uiState.update {
                it.copy(observacion = event.value, submission = CalidadSubmissionState.Idle)
            }
            CalidadUiEvent.RegistrarPrueba -> registrarPrueba()
            CalidadUiEvent.RegistrarProblema -> registrarProblema()
            CalidadUiEvent.ClearSubmissionMessage -> _uiState.update {
                it.copy(submission = CalidadSubmissionState.Idle)
            }
        }
    }

    private fun cargarEntregas() {
        _uiState.update { it.copy(content = CalidadContentState.Loading) }
        viewModelScope.launch {
            try {
                val entregas = obtenerEntregas().sortedByDescending(Entrega::fechaHora)
                val items = entregas.map {
                    CalidadEntregaUi(
                        id = it.id,
                        fechaHora = it.fechaHora,
                        litros = it.litros,
                        tipo = it.tipo,
                        estadoSincronizacion = it.estadoSincronizacion,
                    )
                }
                _uiState.update {
                    it.copy(
                        content = if (items.isEmpty()) CalidadContentState.Empty
                        else CalidadContentState.Success(items),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(content = CalidadContentState.Error("No se pudieron cargar las entregas."))
                }
            }
        }
    }

    private fun seleccionarEntrega(entregaId: String) {
        val entrega = (_uiState.value.content as? CalidadContentState.Success)
            ?.entregas?.firstOrNull { it.id == entregaId } ?: return
        _uiState.update {
            it.copy(
                entregaSeleccionadaId = entregaId,
                entregaSeleccionada = entrega,
                detalle = CalidadDetalleState.Loading,
                observacion = "",
                submission = CalidadSubmissionState.Idle,
            )
        }
        viewModelScope.launch {
            try {
                val prueba = calidadRepository.obtenerPruebaPorEntrega(entregaId)
                val problemas = calidadRepository.obtenerProblemasPorEntrega(entregaId)
                _uiState.update {
                    it.copy(detalle = CalidadDetalleState.Loaded(prueba, problemas))
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(detalle = CalidadDetalleState.Error("No se pudo cargar el detalle de calidad."))
                }
            }
        }
    }

    private fun registrarPrueba() {
        val entregaId = _uiState.value.entregaSeleccionadaId
        if (entregaId == null) {
            mostrarError("Selecciona una entrega antes de registrar la prueba.")
            return
        }
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            mostrarError("Inicia sesión para registrar pruebas de calidad.")
            return
        }
        val detail = _uiState.value.detalle as? CalidadDetalleState.Loaded
        if (detail?.prueba != null) {
            mostrarError("La entrega ya tiene una prueba de calidad registrada.")
            return
        }
        _uiState.update { it.copy(submission = CalidadSubmissionState.Saving) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(usuario.rol, AccionUsuario.REGISTRAR_PRUEBA_CALIDAD)
                val prueba = registrarPruebaCalidad(
                    PruebaCalidad(
                        id = idGenerator(), 
                        entregaId = entregaId,
                        fechaHora = ahora()
                    )
                )
                registrarAuditoria(
                    id = auditIdGenerator(),
                    usuarioId = usuario.id,
                    fechaHora = ahora(),
                    registroAfectadoId = prueba.id,
                    accion = "REGISTRAR_PRUEBA_CALIDAD",
                )
                _uiState.update {
                    it.copy(
                        detalle = CalidadDetalleState.Loaded(prueba, (it.detalle as? CalidadDetalleState.Loaded)?.problemas.orEmpty()),
                        submission = CalidadSubmissionState.Success("Prueba de calidad registrada."),
                    )
                }
            } catch (_: AccesoDenegadoException) {
                mostrarError("Tu rol no tiene permiso para registrar pruebas de calidad.")
            } catch (error: CancellationException) {
                throw error
            } catch (_: EntregaNoEncontradaException) {
                mostrarError("La entrega seleccionada ya no está disponible.")
            } catch (_: PruebaCalidadInvalidaException) {
                mostrarError("La prueba debe estar vinculada a una entrega válida.")
            } catch (_: Exception) {
                mostrarError("No se pudo registrar la prueba de calidad.")
            }
        }
    }

    private fun registrarProblema() {
        val entregaId = _uiState.value.entregaSeleccionadaId
        if (entregaId == null) {
            mostrarError("Selecciona una entrega antes de registrar una observación.")
            return
        }
        val usuario = sesionUsuario.usuario.value
        if (usuario == null) {
            mostrarError("Inicia sesión para registrar observaciones.")
            return
        }
        val descripcion = _uiState.value.observacion.trim()
        if (descripcion.isBlank()) {
            mostrarError("Ingresa una observación o problema.")
            return
        }
        _uiState.update { it.copy(submission = CalidadSubmissionState.Saving) }
        viewModelScope.launch {
            try {
                validarPermisoUsuario.requerir(usuario.rol, AccionUsuario.REGISTRAR_PROBLEMA_CALIDAD)
                val problema = registrarProblemaLeche(
                    ProblemaLeche(
                        id = idGenerator(), 
                        entregaId = entregaId, 
                        descripcion = descripcion,
                        fechaHora = ahora()
                    ),
                )
                registrarAuditoria(
                    id = auditIdGenerator(),
                    usuarioId = usuario.id,
                    fechaHora = ahora(),
                    registroAfectadoId = problema.id,
                    accion = "REGISTRAR_PROBLEMA_LECHE",
                )
                _uiState.update {
                    val detail = it.detalle as? CalidadDetalleState.Loaded
                    it.copy(
                        detalle = CalidadDetalleState.Loaded(
                            prueba = detail?.prueba,
                            problemas = detail?.problemas.orEmpty() + problema,
                        ),
                        observacion = "",
                        submission = CalidadSubmissionState.Success("Observación registrada."),
                    )
                }
            } catch (_: AccesoDenegadoException) {
                mostrarError("Tu rol no tiene permiso para registrar observaciones de calidad.")
            } catch (error: CancellationException) {
                throw error
            } catch (_: EntregaNoEncontradaException) {
                mostrarError("La entrega seleccionada ya no está disponible.")
            } catch (_: Exception) {
                mostrarError("No se pudo registrar la observación.")
            }
        }
    }

    private fun mostrarError(message: String) {
        _uiState.update { it.copy(submission = CalidadSubmissionState.Error(message)) }
    }
}
