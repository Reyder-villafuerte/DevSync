package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.ui.components.EstadoUi
import pe.edu.upeu.milkflow.ui.util.fechaHoraLima

data class LineaComprobante(val productor: String, val litros: Double)

data class ComprobanteDatos(
    val folio: String,
    val fecha: String,
    val estadoJornada: String,
    val totalLitros: Double,
    val socios: Int,
    val paradasPendientes: Int,
    val lineas: List<LineaComprobante>,
) {
    /** Texto plano para el adaptador de impresión. */
    fun aLineasTexto(): List<String> = buildList {
        add("Folio: $folio")
        add("Fecha de cierre: $fecha")
        add("Estado: $estadoJornada")
        add("--------------------------------")
        lineas.forEach { add("${it.productor}: %.2f L".format(it.litros)) }
        add("--------------------------------")
        add("Socios atendidos: $socios")
        add("Paradas pendientes: $paradasPendientes")
        add("TOTAL: %.2f L".format(totalLitros))
    }
}

class ComprobanteViewModel(
    private val jornadaId: String,
    private val jornadas: JornadaRepository,
    private val recolecciones: RecoleccionRepository,
    private val productores: ProductorRepository,
) : ViewModel() {

    private val _estado = MutableStateFlow<EstadoUi<ComprobanteDatos>>(EstadoUi.Cargando)
    val estado: StateFlow<EstadoUi<ComprobanteDatos>> = _estado.asStateFlow()

    init { cargar() }

    fun cargar() {
        _estado.value = EstadoUi.Cargando
        viewModelScope.launch {
            val jornada = jornadas.porId(jornadaId)
            if (jornada == null) {
                _estado.value = EstadoUi.Error("No se encontró la jornada.")
                return@launch
            }
            val recos = recolecciones.observarPorJornada(jornadaId).first()
            val lineas = recos.map { r ->
                LineaComprobante(
                    productor = productores.porId(r.productorId)?.nombreCompleto ?: r.productorId.take(8),
                    litros = r.litros.valor,
                )
            }.sortedBy { it.productor }

            _estado.value = EstadoUi.Contenido(
                ComprobanteDatos(
                    folio = jornada.id.take(8).uppercase(),
                    fecha = jornada.horaCierre?.fechaHoraLima() ?: "—",
                    estadoJornada = jornada.estado.clave,
                    totalLitros = recos.sumOf { it.litros.valor },
                    socios = recos.map { it.productorId }.distinct().size,
                    paradasPendientes = 0,
                    lineas = lineas,
                ),
            )
        }
    }
}
