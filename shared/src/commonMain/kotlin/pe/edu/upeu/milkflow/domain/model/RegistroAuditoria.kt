package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant

data class RegistroAuditoria(
    val id: String,
    val usuarioId: String,
    val fechaHora: Instant,
    val registroAfectadoId: String,
    val accion: String,
) {
    init {
        require(id.isNotBlank()) { "El identificador de auditoría es obligatorio." }
        require(usuarioId.isNotBlank()) { "Debe indicarse quién realizó la modificación." }
        require(registroAfectadoId.isNotBlank()) { "Debe indicarse el registro afectado." }
        require(accion.isNotBlank()) { "Debe indicarse la acción realizada." }
    }
}
