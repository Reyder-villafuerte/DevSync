package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.datetime.DateTimeUnit
import kotlinx.datetime.LocalDate
import kotlinx.datetime.minus
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.ui.util.ZonaLima
import pe.edu.upeu.milkflow.ui.util.horaLima

/** Rango de fechas que entra en el reporte. */
enum class PeriodoReporte(val etiqueta: String) { DIA("Hoy"), SEMANA("Semana"), MES("Mes") }

/** Una entrega dentro de una ruta. */
data class EntregaRuta(
    val nombre: String,
    val codigoPadron: String,
    val litros: Double,
    val hora: String,
)

/** Una ruta del historial: lo que se acopió entre su apertura y su cierre. */
data class RutaReporte(
    val jornadaId: String,
    val fecha: String,
    val horaInicio: String,
    val horaCierre: String?,
    val estado: EstadoJornada,
    val litros: Double,
    val socios: Int,
    val entregas: List<EntregaRuta>,
)

data class ReportesUiState(
    val periodo: PeriodoReporte = PeriodoReporte.DIA,
    val rutas: List<RutaReporte> = emptyList(),
    val totalLitros: Double = 0.0,
    val totalEntregas: Int = 0,
    /** Ruta cuyo detalle está desplegado. */
    val expandida: String? = null,
    val vacio: Boolean = true,
)

/**
 * Reporte de acopio del dispositivo, RUTA POR RUTA.
 *
 * Antes se agregaba por día/semana/mes: con varias rutas abiertas el mismo día
 * (lo habitual mientras se prueba) todo caía en una sola fila y no se veía de
 * qué recorrido salió cada litro. Ahora el corte elegido solo decide qué rango
 * de fechas entra; cada ruta conserva su propia tarjeta con su hora de
 * apertura, su cierre y sus entregas.
 */
class ReportesViewModel(
    jornadas: JornadaRepository,
    recolecciones: RecoleccionRepository,
    productores: ProductorRepository,
) : ViewModel() {

    private val periodo = MutableStateFlow(PeriodoReporte.DIA)
    private val expandida = MutableStateFlow<String?>(null)

    val estado: StateFlow<ReportesUiState> =
        combine(
            jornadas.observarTodas(),
            recolecciones.observarTodas(),
            productores.observarPadron(),
            periodo,
            expandida,
        ) { listaJornadas, recos, padron, p, abierta ->
            val hoy = Clock.System.now().toLocalDateTime(ZonaLima).date
            val desde = when (p) {
                PeriodoReporte.DIA -> hoy
                PeriodoReporte.SEMANA -> hoy.minus(6, DateTimeUnit.DAY)
                PeriodoReporte.MES -> LocalDate(hoy.year, hoy.month, 1)
            }

            val nombres = padron.associateBy { it.id }
            val porJornada = recos.filter { !it.deleted }.groupBy { it.jornadaId }

            val rutas = listaJornadas
                .filter { it.fecha >= desde }
                .map { j ->
                    val entregas = (porJornada[j.id] ?: emptyList())
                        .sortedByDescending { it.horaRegistro }
                        .map { r ->
                            val prod = nombres[r.productorId]
                            EntregaRuta(
                                nombre = prod?.nombreCompleto ?: "Socio no descargado",
                                codigoPadron = prod?.codigoPadron ?: r.productorId.take(8),
                                litros = r.litros.valor,
                                hora = r.horaRegistro.horaLima(),
                            )
                        }
                    RutaReporte(
                        jornadaId = j.id,
                        fecha = j.fecha.toString(),
                        horaInicio = j.horaInicio.horaLima(),
                        horaCierre = j.horaCierre?.horaLima(),
                        estado = j.estado,
                        litros = entregas.sumOf { it.litros },
                        socios = entregas.size,
                        entregas = entregas,
                    )
                }

            ReportesUiState(
                periodo = p,
                rutas = rutas,
                totalLitros = rutas.sumOf { it.litros },
                totalEntregas = rutas.sumOf { it.socios },
                expandida = abierta,
                vacio = rutas.isEmpty(),
            )
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), ReportesUiState())

    fun onPeriodo(p: PeriodoReporte) { periodo.value = p }

    /** Un toque en la tarjeta despliega (o pliega) el detalle de esa ruta. */
    fun alternarDetalle(jornadaId: String) {
        expandida.value = if (expandida.value == jornadaId) null else jornadaId
    }
}
