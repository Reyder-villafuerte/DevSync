package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import pe.edu.upeu.milkflow.domain.model.ReporteLeche
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class ObtenerReporteDiario(
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(fecha: LocalDate, zonaHoraria: TimeZone): ReporteLeche =
        entregaRepository.crearReporte(rangoDeDias(fecha, 1, zonaHoraria))
}
