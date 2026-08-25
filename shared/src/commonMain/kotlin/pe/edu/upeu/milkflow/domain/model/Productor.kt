package pe.edu.upeu.milkflow.domain.model

/**
 * Representa a un productor de leche (socio) en el sistema.
 */
data class Productor(
    val id: String,
    val nombre: String,
    val codigoSocio: String,
    val comunidad: String
) {
    /** Propiedad calculada para visualización en listas o QR. */
    val nombreConCodigo: String
        get() = "$nombre ($codigoSocio)"
}
