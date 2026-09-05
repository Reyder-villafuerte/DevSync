package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.EntregaNoEncontradaException
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class RegistrarProblemaLeche(
    private val entregaRepository: EntregaRepository,
    private val calidadRepository: CalidadRepository,
) {
    suspend operator fun invoke(problema: ProblemaLeche): ProblemaLeche {
        entregaRepository.obtenerPorId(problema.entregaId)
            ?: throw EntregaNoEncontradaException(problema.entregaId)
        return calidadRepository.guardarProblema(problema)
    }
}
