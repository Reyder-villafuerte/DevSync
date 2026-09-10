package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.vo.Dni

/**
 * Aviso para el pop-up del productor. Se considera "activo" cuando su fecha de
 * publicación ya pasó y no está expirado. Gana el servidor.
 */
data class Aviso(
    override val id: String,
    val titulo: String,
    val contenido: String,
    val imagenUrl: String?,
    val obligatorio: Boolean,
    val fechaPublicacion: Instant,
    val fechaExpiracion: Instant?,
    val vistoLocalmente: Boolean,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable {
    fun estaActivo(ahora: Instant): Boolean =
        !deleted && fechaPublicacion <= ahora && (fechaExpiracion == null || fechaExpiracion >= ahora)
}

/** Asistencia a una asamblea, registrada por DNI. Solo inserción en el móvil. */
data class Asistencia(
    override val id: String,
    val asambleaId: String,
    val productorId: String?,
    val dni: Dni,
    val nombreCompleto: String?,
    val registradoEn: Instant,
    val registradoPor: String?,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable
