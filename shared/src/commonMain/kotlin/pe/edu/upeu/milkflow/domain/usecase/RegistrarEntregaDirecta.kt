package pe.edu.upeu.milkflow.domain.usecase

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

class RegistrarEntregaDirecta(
    private val productorRepository: ProductorRepository,
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(
        id: String,
        productorId: String,
        fechaHora: Instant,
        litros: Double,
        usuarioRegistroId: String,
        estadoSincronizacion: EstadoSincronizacion = EstadoSincronizacion.PENDIENTE,
    ): Entrega {
        productorRepository.obtenerProductorActivo(productorId)

        val entrega = Entrega(
            id = id,
            productorId = productorId,
            fechaHora = fechaHora,
            litros = litros,
            tipo = TipoEntrega.DIRECTA,
            usuarioRegistroId = usuarioRegistroId,
            estadoSincronizacion = estadoSincronizacion,
        )
        return entregaRepository.guardar(entrega)
    }
}
