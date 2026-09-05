package pe.edu.upeu.milkflow.presentation.reportes

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.domain.model.ReporteLeche
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteMensual
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteSemanal
import pe.edu.upeu.milkflow.domain.usecase.ObtenerTotalLeche

class ReporteViewModel(
    private val obtenerReporteDiario: ObtenerReporteDiario,
    private val obtenerReporteSemanal: ObtenerReporteSemanal,
    private val obtenerReporteMensual: ObtenerReporteMensual,
    private val obtenerTotalLeche: ObtenerTotalLeche,
    private val zonaHoraria: () -> TimeZone = { TimeZone.currentSystemDefault() },
    private val hoy: () -> LocalDate = {
        Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault()).date
    },
) : ViewModel() {
    private val _uiState = MutableStateFlow(ReporteUiState())
    val uiState: StateFlow<ReporteUiState> = _uiState.asStateFlow()

    init {
        _uiState.update { it.copy(fechaReferencia = hoy().toString()) }
        consultar()
    }

    fun onEvent(event: ReporteUiEvent) {
        when (event) {
            ReporteUiEvent.Load, ReporteUiEvent.Retry -> consultar()
            is ReporteUiEvent.FechaChanged -> _uiState.update { it.copy(fechaReferencia = event.value) }
            is ReporteUiEvent.PeriodoChanged -> _uiState.update { it.copy(periodo = event.value) }
            ReporteUiEvent.Consultar -> consultar()
        }
    }

    private fun consultar() {
        val state = _uiState.value
        val fecha = state.fechaReferencia.parseDateOrNull()
        if (fecha == null) {
            _uiState.update { it.copy(content = ReporteContentState.Error("Usa una fecha válida con el formato AAAA-MM-DD.")) }
            return
        }
        _uiState.update { it.copy(content = ReporteContentState.Loading) }
        viewModelScope.launch {
            try {
                val reporte = when (state.periodo) {
                    ReportePeriodo.DIARIO -> {
                        val generado = obtenerReporteDiario(fecha, zonaHoraria())
                        generado.copy(totalLitros = obtenerTotalLeche(generado.rango))
                    }
                    ReportePeriodo.SEMANAL -> obtenerReporteSemanal(fecha, zonaHoraria())
                    ReportePeriodo.MENSUAL -> obtenerReporteMensual(
                        fecha.year,
                        fecha.monthNumber,
                        zonaHoraria(),
                    )
                }
                _uiState.update {
                    it.copy(
                        content = if (reporte.cantidadEntregas == 0) ReporteContentState.Empty
                        else ReporteContentState.Success(reporte),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update { it.copy(content = ReporteContentState.Error("No se pudo generar el reporte.")) }
            }
        }
    }

    private fun mostrarError(message: String) {
        _uiState.update { it.copy(content = ReporteContentState.Error(message)) }
    }
}
