package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class ObtenerTotalLeche(
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(rango: RangoFechas): Double =
        entregaRepository.obtenerPorRango(rango).sumOf { it.litros }
}
