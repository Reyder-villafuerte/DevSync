package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.ProductorNoEncontradoException
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

class ObtenerEntregasProductor(
    private val productorRepository: ProductorRepository,
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(productorId: String, rango: RangoFechas? = null): List<Entrega> {
        productorRepository.obtenerPorId(productorId)
            ?: throw ProductorNoEncontradoException(productorId)
        return entregaRepository.obtenerPorProductor(productorId, rango)
    }
}
