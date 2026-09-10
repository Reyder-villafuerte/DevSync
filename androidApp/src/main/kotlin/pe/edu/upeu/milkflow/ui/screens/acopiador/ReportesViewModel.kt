package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import pe.edu.upeu.milkflow.domain.reporte.ReporteAcopio
import pe.edu.upeu.milkflow.domain.reporte.TotalPeriodo
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteAcopioUseCase

enum class PeriodoReporte(val etiqueta: String) { DIA("Día"), SEMANA("Semana"), MES("Mes") }

data class ReportesUiState(
    val periodo: PeriodoReporte = PeriodoReporte.DIA,
    val filas: List<TotalPeriodo> = emptyList(),
    val totalLitros: Double = 0.0,
    val totalRecolecciones: Int = 0,
    val vacio: Boolean = true,
)

/**
 * Reportes de acopio. `ObtenerReporteAcopioUseCase` ya agrega por día/semana/mes
 * sobre el Flow local; aquí sólo se elige qué corte mostrar.
 */
class ReportesViewModel(
    obtenerReporte: ObtenerReporteAcopioUseCase,
) : ViewModel() {

    private val periodo = MutableStateFlow(PeriodoReporte.DIA)

    val estado: StateFlow<ReportesUiState> =
        combine(obtenerReporte(null), periodo) { reporte: ReporteAcopio, p ->
            val filas = when (p) {
                PeriodoReporte.DIA -> reporte.porDia
                PeriodoReporte.SEMANA -> reporte.porSemana
                PeriodoReporte.MES -> reporte.porMes
            }.sortedByDescending { it.etiqueta }

            ReportesUiState(
                periodo = p,
                filas = filas,
                totalLitros = filas.sumOf { it.litros.valor },
                totalRecolecciones = filas.sumOf { it.recolecciones },
                vacio = filas.isEmpty(),
            )
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), ReportesUiState())

    fun onPeriodo(p: PeriodoReporte) { periodo.value = p }
}
