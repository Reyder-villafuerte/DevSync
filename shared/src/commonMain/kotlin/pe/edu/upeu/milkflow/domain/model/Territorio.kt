package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant

data class Ruta(
    override val id: String,
    val nombre: String,
    val codigo: String?,
    val activa: Boolean,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable

data class Zona(
    override val id: String,
    val nombre: String,
    val codigo: String?,
    val rutaId: String,
    val activa: Boolean,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable

/** Solicitud del productor para cambiar de zona/ruta; la aprueba administración. */
data class SolicitudRuta(
    override val id: String,
    val productorId: String,
    val zonaActualId: String,
    val zonaSolicitadaId: String,
    val motivo: String,
    val estado: EstadoSolicitud,
    val resueltoEn: Instant?,
    val comentarioResolucion: String?,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable

enum class EstadoSolicitud(val clave: String) {
    PENDIENTE("pendiente"), APROBADA("aprobada"), RECHAZADA("rechazada");

    companion object {
        fun desde(clave: String) = entries.firstOrNull { it.clave == clave } ?: PENDIENTE
    }
}
