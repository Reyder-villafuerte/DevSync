package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant

data class LoteProduccion(
    val id: String,
    val fechaHora: Instant,
    val litrosLecheUtilizados: Double,
    val moldesObtenidos: Int,
    val tipoProducto: TipoProducto,
    val observaciones: String? = null,
) {
    val rendimiento: Double?
        get() = if (tipoProducto.esQueso() && litrosLecheUtilizados > 0) {
            (moldesObtenidos.toDouble() / litrosLecheUtilizados) * 100.0
        } else null

    val dentroDelRango: Boolean?
        get() = rendimiento?.let { it in 11.0..12.0 }
}
