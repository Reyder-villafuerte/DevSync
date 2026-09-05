package pe.edu.upeu.milkflow.domain.model

data class ReporteLeche(
    val rango: RangoFechas,
    val totalLitros: Double,
    val cantidadEntregas: Int,
)

data class ResumenProductor(
    val productorId: String,
    val rango: RangoFechas,
    val totalLitros: Double,
    val cantidadEntregas: Int,
)
