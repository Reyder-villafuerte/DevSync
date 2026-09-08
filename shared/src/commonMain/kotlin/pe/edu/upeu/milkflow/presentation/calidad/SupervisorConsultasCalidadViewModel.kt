package pe.edu.upeu.milkflow.presentation.calidad

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlin.time.Instant
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.usecase.rangoDeDias

class SupervisorConsultasCalidadViewModel(
    private val calidadRepository: CalidadRepository,
    private val entregaRepository: EntregaRepository,
    private val ahora: () -> Instant = { Clock.System.now() },
    private val zonaHoraria: () -> TimeZone = { TimeZone.currentSystemDefault() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(SupervisorConsultasUiState())
    val uiState: StateFlow<SupervisorConsultasUiState> = _uiState.asStateFlow()

    init {
        cargarInspeccionesHoy()
        cargarProblemas()
    }

    fun onEvent(event: SupervisorConsultasUiEvent) {
        when (event) {
            SupervisorConsultasUiEvent.RefreshInspecciones -> cargarInspeccionesHoy()
            SupervisorConsultasUiEvent.RefreshProblemas -> cargarProblemas()
        }
    }

    private fun cargarInspeccionesHoy() {
        _uiState.update { it.copy(inspeccionesHoy = InspeccionesHoyState.Loading) }
        viewModelScope.launch {
            try {
                val zone = zonaHoraria()
                val fecha = ahora().toLocalDateTime(zone).date
                val rango = rangoDeDias(fecha, 1, zone)
                
                val pruebas = calidadRepository.obtenerPruebasPorRango(rango)
                val items = pruebas.map { prueba ->
                    val problemas = calidadRepository.obtenerProblemasPorEntrega(prueba.entregaId)
                    val entrega = entregaRepository.obtenerPorId(prueba.entregaId)
                    InspeccionUiItem(
                        prueba = prueba,
                        problemas = problemas,
                        litrosEntrega = entrega?.litros ?: 0.0
                    )
                }
                
                _uiState.update {
                    it.copy(
                        inspeccionesHoy = if (items.isEmpty()) InspeccionesHoyState.Empty
                        else InspeccionesHoyState.Success(items)
                    )
                }
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                _uiState.update { it.copy(inspeccionesHoy = InspeccionesHoyState.Error("No se pudieron cargar las inspecciones.")) }
            }
        }
    }

    private fun cargarProblemas() {
        _uiState.update { it.copy(problemasCalidad = ProblemasCalidadState.Loading) }
        viewModelScope.launch {
            try {
                val problemas = calidadRepository.obtenerTodosLosProblemas()
                _uiState.update {
                    it.copy(
                        problemasCalidad = if (problemas.isEmpty()) ProblemasCalidadState.Empty
                        else ProblemasCalidadState.Success(problemas)
                    )
                }
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                _uiState.update { it.copy(problemasCalidad = ProblemasCalidadState.Error("No se pudieron cargar los problemas.")) }
            }
        }
    }
}
