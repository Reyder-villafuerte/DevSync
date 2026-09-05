package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.ProductorNoEncontradoException
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

class ActualizarProductor(
    private val productorRepository: ProductorRepository,
) {
    suspend operator fun invoke(productor: Productor): Productor {
        productorRepository.obtenerPorId(productor.id)
            ?: throw ProductorNoEncontradoException(productor.id)
        return productorRepository.guardar(productor)
    }
}
