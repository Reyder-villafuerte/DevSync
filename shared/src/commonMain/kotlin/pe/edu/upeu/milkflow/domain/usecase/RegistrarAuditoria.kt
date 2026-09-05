package pe.edu.upeu.milkflow.domain.usecase

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository

class RegistrarAuditoria(
    private val auditoriaRepository: AuditoriaRepository,
) {
    suspend operator fun invoke(
        id: String,
        usuarioId: String,
        fechaHora: Instant,
        registroAfectadoId: String,
        accion: String,
    ): RegistroAuditoria {
        val registro = RegistroAuditoria(
            id = id,
            usuarioId = usuarioId,
            fechaHora = fechaHora,
            registroAfectadoId = registroAfectadoId,
            accion = accion,
        )
        return auditoriaRepository.guardar(registro)
    }
}
