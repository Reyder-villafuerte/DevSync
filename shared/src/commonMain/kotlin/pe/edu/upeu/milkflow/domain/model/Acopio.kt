package pe.edu.upeu.milkflow.domain.model

/**
 * Entidad principal de MilkFlow que registra la entrega de leche.
 */
data class Acopio(
    val id: String,
    val fecha: Long,
    val productorId: String,
    val litros: Double,
    val precioAplicado: Double,
    val notaCalidad: String? = null,
    val estadoSinc: EstadoSincronizacion = EstadoSincronizacion.PENDIENTE
) {
    /**
     * Regla de negocio: Cálculo del importe total a pagar al productor.
     */
    val totalPago: Double
        get() = litros * precioAplicado

    /**
     * Validación de negocio: Un acopio es válido solo si tiene litros positivos.
     */
    val esValido: Boolean
        get() = litros > 0 && precioAplicado >= 0
}
