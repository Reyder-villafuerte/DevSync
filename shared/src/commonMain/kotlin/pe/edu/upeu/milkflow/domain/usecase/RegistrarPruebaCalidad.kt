package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.EntregaNoEncontradaException
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class RegistrarPruebaCalidad(
    private val entregaRepository: EntregaRepository,
    private val calidadRepository: CalidadRepository,
) {
    suspend operator fun invoke(prueba: PruebaCalidad): PruebaCalidad {
        entregaRepository.obtenerPorId(prueba.entregaId)
            ?: throw EntregaNoEncontradaException(prueba.entregaId)
        return calidadRepository.guardarPrueba(prueba)
    }
}
