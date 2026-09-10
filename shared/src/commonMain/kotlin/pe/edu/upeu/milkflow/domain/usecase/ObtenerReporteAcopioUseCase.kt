package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.reporte.ReporteAcopio
import pe.edu.upeu.milkflow.domain.reporte.TotalPeriodo
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.vo.Litros

/**
 * Intención: "ver cuánta leche se acopió, agregada por día, semana y mes".
 *
 * Agrega en cliente sobre las recolecciones locales (Flow), así el reporte
 * funciona sin red y se actualiza al vuelo cuando entra una recolección nueva.
 * La zona horaria es fija (America/Lima) para que el corte de día coincida con
 * el del backend y con el ciclo de pago.
 */
class ObtenerReporteAcopioUseCase(
    private val recolecciones: RecoleccionRepository,
    private val zona: TimeZone = TimeZone.of("America/Lima"),
) {
    /** @param productorId si es null, agrega todas las recolecciones del dispositivo. */
    operator fun invoke(productorId: String? = null): Flow<ReporteAcopio> =
        recolecciones.observarTodas().map { todas ->
            val items = todas.filter { !it.deleted && (productorId == null || it.productorId == productorId) }
            if (items.isEmpty()) ReporteAcopio.VACIO else agregar(items)
        }

    private fun agregar(items: List<Recoleccion>): ReporteAcopio {
        val porDia = agrupar(items) { clavesDia(it).primera }
        val porSemana = agrupar(items) { clavesDia(it).semana }
        val porMes = agrupar(items) { clavesDia(it).mes }
        return ReporteAcopio(porDia, porSemana, porMes)
    }

    private inline fun agrupar(items: List<Recoleccion>, clave: (Recoleccion) -> String): List<TotalPeriodo> =
        items.groupBy(clave)
            .map { (etiqueta, grupo) ->
                TotalPeriodo(
                    etiqueta = etiqueta,
                    litros = Litros.sumar(grupo.map { it.litros }),
                    recolecciones = grupo.size,
                )
            }
            .sortedBy { it.etiqueta }

    private data class Claves(val primera: String, val semana: String, val mes: String)

    private fun clavesDia(r: Recoleccion): Claves {
        val fecha = r.horaRegistro.toLocalDateTime(zona).date
        val dia = fecha.toString()                       // yyyy-MM-dd
        val mes = dia.substring(0, 7)                     // yyyy-MM
        // Semana aproximada por número de día del año / 7; suficiente para el reporte.
        val semana = "${dia.substring(0, 4)}-W${((fecha.dayOfYear - 1) / 7 + 1).pad2()}"
        return Claves(dia, semana, mes)
    }

    private fun Int.pad2(): String = if (this < 10) "0$this" else "$this"
}
