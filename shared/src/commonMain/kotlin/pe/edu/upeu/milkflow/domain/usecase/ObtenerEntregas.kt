package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class ObtenerEntregas(
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(): List<Entrega> = entregaRepository.obtenerTodas()
}
