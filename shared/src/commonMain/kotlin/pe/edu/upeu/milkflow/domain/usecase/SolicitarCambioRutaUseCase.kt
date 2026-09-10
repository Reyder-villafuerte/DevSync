package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.EstadoSolicitud
import pe.edu.upeu.milkflow.domain.model.SolicitudRuta
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.SolicitudRutaRepository

/**
 * Intención: "el productor pide que lo cambien de zona/ruta".
 *
 * La aprueba administración desde el panel web; el móvil solo crea la solicitud
 * en estado pendiente (local + outbox).
 */
class SolicitarCambioRutaUseCase(
    private val productores: ProductorRepository,
    private val solicitudes: SolicitudRutaRepository,
    private val generadorId: GeneradorId,
    private val reloj: Reloj,
) {
    data class Entrada(
        val productorId: String,
        val zonaSolicitadaId: String,
        val motivo: String,
    )

    suspend operator fun invoke(entrada: Entrada): Resultado<SolicitudRuta> {
        val motivoFinal = entrada.motivo.ifBlank { "Solicitud de cambio de zona" }.trim()
        val productor = productores.porId(entrada.productorId)
            ?: return Resultado.Fallo(ErrorApp.Validacion("Productor", "no encontrado"))
        if (productor.zonaId == entrada.zonaSolicitadaId) {
            return Resultado.Fallo(ErrorApp.Validacion("Zona", "ya pertenece a esa zona"))
        }

        val ahora = reloj.ahora()
        val solicitud = SolicitudRuta(
            id = generadorId.nuevo(),
            productorId = productor.id,
            zonaActualId = productor.zonaId,
            zonaSolicitadaId = entrada.zonaSolicitadaId,
            motivo = motivoFinal,
            estado = EstadoSolicitud.PENDIENTE,
            resueltoEn = null,
            comentarioResolucion = null,
            updatedAt = ahora,
            version = 0,
            deleted = false,
        )
        return solicitudes.crear(solicitud)
    }
}
