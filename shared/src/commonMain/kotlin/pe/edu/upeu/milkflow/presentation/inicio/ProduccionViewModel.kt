package pe.edu.upeu.milkflow.presentation.inicio

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
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.usecase.ObtenerLotesProduccion
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLoteProduccion
import pe.edu.upeu.milkflow.domain.usecase.rangoDeDias
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class ProduccionViewModel(
    private val registrarLoteProduccion: RegistrarLoteProduccion,
    private val obtenerLotesProduccion: ObtenerLotesProduccion,
    private val sesionUsuario: SesionUsuario,
    private val registrarAuditoria: RegistrarAuditoria,
    private val ahora: () -> kotlin.time.Instant = { Clock.System.now() },
    private val idGenerator: () -> String = { Uuid.random().toString() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProduccionUiState())
    val uiState: StateFlow<ProduccionUiState> = _uiState.asStateFlow()

    init {
        cargarLotes()
    }

    fun onEvent(event: ProduccionUiEvent) {
        when (event) {
            ProduccionUiEvent.Load -> cargarLotes()
            is ProduccionUiEvent.SetSoloHoy -> {
                _uiState.update { it.copy(soloHoy = event.value) }
                cargarLotes()
            }
            is ProduccionUiEvent.TipoProductoChanged -> _uiState.update { 
                it.copy(tipoProducto = event.value) 
            }
            is ProduccionUiEvent.LitrosLecheChanged -> _uiState.update { 
                it.copy(litrosLeche = event.value) 
            }
            is ProduccionUiEvent.MoldesChanged -> _uiState.update { 
                it.copy(moldes = event.value) 
            }
            is ProduccionUiEvent.ObservacionesChanged -> _uiState.update { 
                it.copy(observaciones = event.value) 
            }
            ProduccionUiEvent.RegistrarLote -> registrarLote()
            ProduccionUiEvent.ClearSubmissionMessage -> _uiState.update { 
                it.copy(submission = ProduccionSubmissionState.Idle) 
            }
        }
    }

    private fun cargarLotes() {
        _uiState.update { it.copy(content = ProduccionContentState.Loading) }
        viewModelScope.launch {
            try {
                obtenerLotesProduccion.observarTodos().collect { todos ->
                    val lotes = if (_uiState.value.soloHoy) {
                        val zone = TimeZone.currentSystemDefault()
                        val hoy = ahora().toLocalDateTime(zone).date
                        val rango = rangoDeDias(hoy, 1, zone)
                        todos.filter { it.fechaHora in rango }
                    } else todos
                    
                    _uiState.update { 
                        it.copy(
                            content = if (lotes.isEmpty()) ProduccionContentState.Empty else ProduccionContentState.Success(lotes),
                            lotes = lotes
                        )
                    }
                }
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                _uiState.update { it.copy(content = ProduccionContentState.Error("Error al cargar lotes")) }
            }
        }
    }

    private fun registrarLote() {
        val state = _uiState.value
        val litros = state.litrosLeche.toDoubleOrNull() ?: return
        val moldes = state.moldes.toIntOrNull() ?: 0
        val usuario = sesionUsuario.usuario.value ?: return

        _uiState.update { it.copy(submission = ProduccionSubmissionState.Saving) }
        viewModelScope.launch {
            try {
                val lote = LoteProduccion(
                    id = idGenerator(),
                    fechaHora = ahora(),
                    litrosLecheUtilizados = litros,
                    moldesObtenidos = moldes,
                    tipoProducto = state.tipoProducto,
                    observaciones = state.observaciones.takeIf { it.isNotBlank() }
                )
                registrarLoteProduccion(lote)
                
                registrarAuditoria(
                    id = Uuid.random().toString(),
                    usuarioId = usuario.id,
                    fechaHora = ahora(),
                    registroAfectadoId = lote.id,
                    accion = "REGISTRAR_LOTE_PRODUCCION"
                )

                _uiState.update { 
                    it.copy(
                        litrosLeche = "",
                        moldes = "",
                        observaciones = "",
                        submission = ProduccionSubmissionState.Success("Lote registrado correctamente.")
                    ) 
                }
            } catch (e: CancellationException) {
                throw e
            } catch (e: IllegalArgumentException) {
                _uiState.update { it.copy(submission = ProduccionSubmissionState.Error(e.message ?: "Datos inválidos")) }
            } catch (e: Exception) {
                _uiState.update { it.copy(submission = ProduccionSubmissionState.Error(e.message ?: "Error al registrar")) }
            }
        }
    }
}
