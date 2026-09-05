package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.ProductorNoEncontradoException
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.ResumenProductor
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

class ObtenerResumenProductor(
    private val productorRepository: ProductorRepository,
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(productorId: String, rango: RangoFechas): ResumenProductor {
        productorRepository.obtenerPorId(productorId)
            ?: throw ProductorNoEncontradoException(productorId)
        val entregas = entregaRepository.obtenerPorProductor(productorId, rango)
        return ResumenProductor(
            productorId = productorId,
            rango = rango,
            totalLitros = entregas.sumOf { it.litros },
            cantidadEntregas = entregas.size,
        )
    }
}
