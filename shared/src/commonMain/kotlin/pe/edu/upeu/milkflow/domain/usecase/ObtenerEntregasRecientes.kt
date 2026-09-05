package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class ObtenerEntregasRecientes(
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(
        rango: RangoFechas,
        limite: Int = 5,
    ): List<Entrega> {
        require(limite > 0) { "El límite de entregas debe ser mayor que cero." }
        return entregaRepository.obtenerPorRango(rango)
            .sortedByDescending(Entrega::fechaHora)
            .take(limite)
    }
}
