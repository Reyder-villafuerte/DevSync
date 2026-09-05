package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.ProductorInactivoException
import pe.edu.upeu.milkflow.domain.ProductorNoEncontradoException
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

internal suspend fun ProductorRepository.obtenerProductorActivo(productorId: String): Productor {
    val productor = obtenerPorId(productorId)
        ?: throw ProductorNoEncontradoException(productorId)
    if (!productor.activo) {
        throw ProductorInactivoException(productorId)
    }
    return productor
}
