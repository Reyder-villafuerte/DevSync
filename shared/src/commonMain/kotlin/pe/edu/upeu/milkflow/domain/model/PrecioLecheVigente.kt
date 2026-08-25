package pe.edu.upeu.milkflow.domain.model

data class PrecioLecheVigente(
    val id: String,
    val precioPorLitro: Double,
    val fechaInicio: String,
    val fechaFin: String? = null,
    val actualizadoPorUsuarioId: String? = null
) {
    init {
        require(id.isNotBlank()) { "id es obligatorio" }
        require(precioPorLitro > 0) { "precioPorLitro debe ser mayor que 0" }
        require(fechaInicio.isNotBlank()) { "fechaInicio es obligatoria" }
        require(fechaFin == null || fechaFin >= fechaInicio) {
            "fechaFin no puede ser menor a fechaInicio"
        }
    }

    /**
     * Supone fechas en formato ISO local: YYYY-MM-DD.
     */
    fun estaVigenteEn(fecha: String): Boolean {
        require(fecha.isNotBlank()) { "fecha es obligatoria" }
        val inicioCumplido = fecha >= fechaInicio
        val finCumplido = fechaFin == null || fecha <= fechaFin
        return inicioCumplido && finCumplido
    }
}
