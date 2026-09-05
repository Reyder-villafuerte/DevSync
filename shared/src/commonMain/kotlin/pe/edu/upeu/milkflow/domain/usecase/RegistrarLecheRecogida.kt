package pe.edu.upeu.milkflow.domain.usecase

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.AcopiadorNoEncontradoException
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

class RegistrarLecheRecogida(
    private val productorRepository: ProductorRepository,
    private val acopiadorRepository: AcopiadorRepository,
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(
        id: String,
        productorId: String,
        fechaHora: Instant,
        litros: Double,
        usuarioRegistroId: String,
        acopiadorId: String,
        sector: String,
        estadoSincronizacion: EstadoSincronizacion = EstadoSincronizacion.PENDIENTE,
    ): Entrega {
        productorRepository.obtenerProductorActivo(productorId)
        acopiadorRepository.obtenerPorId(acopiadorId)
            ?: throw AcopiadorNoEncontradoException(acopiadorId)

        val entrega = Entrega(
            id = id,
            productorId = productorId,
            fechaHora = fechaHora,
            litros = litros,
            tipo = TipoEntrega.RECOGIDA,
            usuarioRegistroId = usuarioRegistroId,
            estadoSincronizacion = estadoSincronizacion,
            acopiadorId = acopiadorId,
            sector = sector,
        )
        return entregaRepository.guardar(entrega)
    }
}
