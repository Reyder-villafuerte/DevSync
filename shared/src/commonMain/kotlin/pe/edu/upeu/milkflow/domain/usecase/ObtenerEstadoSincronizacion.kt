package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository

class ObtenerEstadoSincronizacion(
    private val sincronizacionRepository: SincronizacionRepository,
) {
    suspend operator fun invoke(tipoRegistro: String, registroId: String): EstadoSincronizacion? {
        require(tipoRegistro.isNotBlank()) { "El tipo de registro es obligatorio." }
        require(registroId.isNotBlank()) { "El identificador del registro es obligatorio." }
        return sincronizacionRepository.obtenerEstado(tipoRegistro, registroId)
    }
}
