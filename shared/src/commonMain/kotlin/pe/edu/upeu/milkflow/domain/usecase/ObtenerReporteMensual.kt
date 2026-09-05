package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.ReporteLeche
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class ObtenerReporteMensual(
    private val entregaRepository: EntregaRepository,
) {
    suspend operator fun invoke(anio: Int, mes: Int, zonaHoraria: TimeZone): ReporteLeche {
        val fechaInicial = LocalDate(anio, mes, 1)
        val fechaFinal = if (mes == 12) {
            LocalDate(anio + 1, 1, 1)
        } else {
            LocalDate(anio, mes + 1, 1)
        }
        val rango = RangoFechas(
            inicio = fechaInicial.atStartOfDayIn(zonaHoraria),
            finExclusivo = fechaFinal.atStartOfDayIn(zonaHoraria),
        )
        return entregaRepository.crearReporte(rango)
    }
}
