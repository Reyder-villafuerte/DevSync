package pe.edu.upeu.milkflow.domain.reporte

import pe.edu.upeu.milkflow.domain.vo.Litros

/** Total de un periodo (un día, una semana o un mes). */
data class TotalPeriodo(
    val etiqueta: String,     // "2026-09-09", "2026-W37", "2026-09"
    val litros: Litros,
    val recolecciones: Int,
)

data class ReporteAcopio(
    val porDia: List<TotalPeriodo>,
    val porSemana: List<TotalPeriodo>,
    val porMes: List<TotalPeriodo>,
) {
    val totalLitros: Litros get() = Litros.sumar(porDia.map { it.litros })

    companion object {
        val VACIO = ReporteAcopio(emptyList(), emptyList(), emptyList())
    }
}
