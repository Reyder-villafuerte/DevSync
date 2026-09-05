package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

class RegistrarProductor(
    private val productorRepository: ProductorRepository,
) {
    suspend operator fun invoke(productor: Productor): Productor =
        productorRepository.guardar(productor)
}
