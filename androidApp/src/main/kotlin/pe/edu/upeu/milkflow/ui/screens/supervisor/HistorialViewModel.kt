package pe.edu.upeu.milkflow.ui.screens.supervisor

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlin.time.Duration.Companion.days
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import pe.edu.upeu.milkflow.domain.model.Inspeccion
import pe.edu.upeu.milkflow.domain.repository.InspeccionRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.ui.util.coincideSinAcentos
import pe.edu.upeu.milkflow.ui.util.fechaHoraLima
import pe.edu.upeu.milkflow.ui.util.horaLima

enum class RangoHistorial(val etiqueta: String, val dias: Int) { DIA("Día", 1), SEMANA("Semana", 7), MES("Mes", 30) }

data class InspeccionItem(
    val id: String,
    val fecha: String,
    val hora: String,
    val supervisor: String,
    val mediciones: String,
    val dictamen: String,
    val rechazaLote: Boolean,
    val lineasActa: List<String>,
)

data class HistorialUiState(
    val busqueda: String = "",
    val productoresCoincidentes: List<ProductorItem> = emptyList(),
    val productorSeleccionado: ProductorItem? = null,
    val rango: RangoHistorial = RangoHistorial.SEMANA,
    val inspecciones: List<InspeccionItem> = emptyList(),
)

@OptIn(ExperimentalCoroutinesApi::class)
class HistorialViewModel(
    productores: ProductorRepository,
    private val inspecciones: InspeccionRepository,
) : ViewModel() {

    private val busqueda = MutableStateFlow("")
    private val seleccionado = MutableStateFlow<ProductorItem?>(null)
    private val rango = MutableStateFlow(RangoHistorial.SEMANA)

    private val coincidencias = combine(productores.observarPadron(), busqueda) { padron, q ->
        if (q.isBlank()) emptyList()
        else padron.filter { it.nombreCompleto.coincideSinAcentos(q) || it.codigoPadron.coincideSinAcentos(q) }
            .take(10)
            .map { ProductorItem(it.id, it.nombreCompleto, it.codigoPadron) }
    }

    private val historial = combine(seleccionado, rango) { prod, r -> prod to r }
        .flatMapLatest { (prod, r) ->
            if (prod == null) flowOf(emptyList())
            else inspecciones.observarPorProductor(prod.id).map { lista ->
                val corte = Clock.System.now() - r.dias.days
                lista.filter { it.tomadoEn >= corte }.map { it.aItem() }
            }
        }

    val estado: StateFlow<HistorialUiState> =
        combine(busqueda, coincidencias, seleccionado, rango, historial) { q, coin, sel, r, hist ->
            HistorialUiState(
                busqueda = q,
                productoresCoincidentes = coin,
                productorSeleccionado = sel,
                rango = r,
                inspecciones = hist,
            )
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), HistorialUiState())

    fun onBusqueda(v: String) { busqueda.value = v; if (v.isBlank()) seleccionado.value = null }
    fun onProductor(p: ProductorItem) { seleccionado.value = p; busqueda.value = p.nombre }
    fun onRango(r: RangoHistorial) { rango.value = r }

    private fun Inspeccion.aItem(): InspeccionItem {
        val m = medicion
        val mediciones = buildList {
            m.aguaAnadidaPorcentaje?.let { add("agua ${it}%") }
            m.ph?.let { add("pH $it") }
            m.densidad?.let { add("dens. $it") }
            m.temperatura?.let { add("${it}°C") }
        }.joinToString("  ·  ").ifBlank { "—" }

        return InspeccionItem(
            id = id,
            fecha = tomadoEn.fechaHoraLima().substringBefore(" "),
            hora = tomadoEn.horaLima(),
            supervisor = supervisorId.take(8).uppercase(),
            mediciones = mediciones,
            dictamen = dictamen.clave,
            rechazaLote = dictamen.rechazaLote,
            lineasActa = listOf(
                "ACTA DE INSPECCIÓN ${id.take(8).uppercase()}",
                "Fecha: ${tomadoEn.fechaHoraLima()}",
                "Supervisor: ${supervisorId.take(8).uppercase()}",
                "Mediciones: $mediciones",
                "--------------------------------",
                "Dictamen: ${dictamen.clave}",
                dictamenDetalle,
                if (esReincidencia) "REINCIDENCIA" else "",
            ).filter { it.isNotBlank() },
        )
    }
}
