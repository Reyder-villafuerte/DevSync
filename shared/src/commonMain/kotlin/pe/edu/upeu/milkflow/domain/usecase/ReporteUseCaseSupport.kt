package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.datetime.DatePeriod
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import kotlinx.datetime.plus
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.ReporteLeche
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

internal fun rangoDeDias(
    fechaInicial: LocalDate,
    cantidadDias: Int,
    zonaHoraria: TimeZone,
): RangoFechas = RangoFechas(
    inicio = fechaInicial.atStartOfDayIn(zonaHoraria),
    finExclusivo = fechaInicial.plus(DatePeriod(days = cantidadDias)).atStartOfDayIn(zonaHoraria),
)

internal suspend fun EntregaRepository.crearReporte(rango: RangoFechas): ReporteLeche {
    val entregas = obtenerPorRango(rango)
    return ReporteLeche(
        rango = rango,
        totalLitros = entregas.sumOf { it.litros },
        cantidadEntregas = entregas.size,
    )
}
