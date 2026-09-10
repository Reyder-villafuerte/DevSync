package pe.edu.upeu.milkflow.ui.screens.productor

import androidx.compose.ui.graphics.ImageBitmap
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.DateTimeUnit
import kotlinx.datetime.DayOfWeek
import kotlinx.datetime.LocalDate
import kotlinx.datetime.plus
import kotlinx.datetime.previousOrSame
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.Aviso
import pe.edu.upeu.milkflow.domain.model.ConceptoPrecio
import pe.edu.upeu.milkflow.domain.model.EstadoSolicitud
import pe.edu.upeu.milkflow.domain.repository.AvisoRepository
import pe.edu.upeu.milkflow.domain.repository.InspeccionRepository
import pe.edu.upeu.milkflow.domain.repository.LiquidacionRepository
import pe.edu.upeu.milkflow.domain.model.EstadoRecepcion
import pe.edu.upeu.milkflow.domain.repository.PrecioRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SolicitudRutaRepository
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerAvisoActivoUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteAcopioUseCase
import pe.edu.upeu.milkflow.domain.usecase.SincronizarAhoraUseCase
import pe.edu.upeu.milkflow.domain.usecase.SolicitarCambioRutaUseCase
import pe.edu.upeu.milkflow.ui.util.CargadorImagenRemota
import pe.edu.upeu.milkflow.ui.util.ZonaLima
import pe.edu.upeu.milkflow.ui.util.mensajeUi

data class ZonaOpcion(val id: String, val nombre: String)

/** Una entrega del productor y cómo la recibió el jefe de producción en planta. */
data class EntregaResumen(
    val fecha: String,
    val litros: Double,
    val estadoRecepcion: EstadoRecepcion,
    val litrosFaltantes: Double,
)

data class SolicitudSheet(
    val zonaSeleccionadaId: String? = null,
    val motivo: String = "",
    val guardando: Boolean = false,
    val error: String? = null,
)

data class ProductorUiState(
    val cargando: Boolean = true,
    val nombre: String = "",
    val litrosHoy: Double = 0.0,
    val estadoCalidad: String = "Sin inspecciones",
    val calidadRechaza: Boolean = false,
    val litrosCiclo: Double = 0.0,
    val tarifaLitro: Double = 0.0,
    val pagoProyectado: Double = 0.0,
    val ultimaLiquidacionNeto: Double? = null,
    val rutaAsignada: String = "—",
    val zonaActualId: String? = null,
    val zonas: List<ZonaOpcion> = emptyList(),
    val estadoUltimaSolicitud: EstadoSolicitud? = null,
    val entregas: List<EntregaResumen> = emptyList(),
    val litrosFaltantesCiclo: Double = 0.0,
    // Aviso obligatorio (pop-up)
    val aviso: Aviso? = null,
    val avisoImagen: ImageBitmap? = null,
    val sheet: SolicitudSheet? = null,
)

class ProductorViewModel(
    sesion: SesionActiva,
    obtenerAviso: ObtenerAvisoActivoUseCase,
    private val avisos: AvisoRepository,
    productores: ProductorRepository,
    zonas: ZonaRepository,
    rutas: RutaRepository,
    inspecciones: InspeccionRepository,
    obtenerReporte: ObtenerReporteAcopioUseCase,
    precios: PrecioRepository,
    liquidaciones: LiquidacionRepository,
    private val sanciones: SancionRepository,
    private val solicitudes: SolicitudRutaRepository,
    private val solicitarCambio: SolicitarCambioRutaUseCase,
    private val sincronizarAhora: SincronizarAhoraUseCase,
    recolecciones: RecoleccionRepository,
) : ViewModel() {

    private val productorId = (sesion.ambito as? Ambito.Productor)?.id ?: sesion.usuarioId

    private val avisoFlow = MutableStateFlow<Aviso?>(null)
    private val avisoImagen = MutableStateFlow<ImageBitmap?>(null)
    private val sheet = MutableStateFlow<SolicitudSheet?>(null)

    init {
        // El aviso obligatorio se observa y, si trae imagen, se descarga sin librería.
        obtenerAviso()
            .onEach { a ->
                avisoFlow.value = a
                avisoImagen.value = if (a?.imagenUrl != null) CargadorImagenRemota.cargar(a.imagenUrl) else null
            }
            .launchIn(viewModelScope)
    }

    private data class Nucleo(
        val nombre: String,
        val litrosHoy: Double,
        val litrosCiclo: Double,
        val tarifa: Double,
        val calidad: String,
        val calidadRechaza: Boolean,
        val liquidacionNeto: Double?,
        val ruta: String,
        val zonaActualId: String?,
        val zonas: List<ZonaOpcion>,
        val ultimaSolicitud: EstadoSolicitud?,
    )

    private val nucleo = combine(
        obtenerReporte(productorId),
        inspecciones.observarPorProductor(productorId),
        precios.observarVigentes(),
        combine(productores.observarPadron(), zonas.observarTodas(), rutas.observarTodas()) { padron, zs, rs ->
            val p = padron.firstOrNull { it.id == productorId }
            val zona = zs.firstOrNull { it.id == p?.zonaId }
            val ruta = rs.firstOrNull { it.id == zona?.rutaId }
            Triple(p?.nombreCompleto ?: "", (ruta?.nombre ?: "—") to p?.zonaId, zs.map { ZonaOpcion(it.id, it.nombre) })
        },
        combine(liquidaciones.observarPorProductor(productorId), solicitudes.observarMisSolicitudes(productorId)) { liqs, sols ->
            liqs.maxByOrNull { it.updatedAt }?.montoNeto?.valor to sols.maxByOrNull { it.updatedAt }?.estado
        },
    ) { reporte, inspeccionesLista, preciosVigentes, datosRuta, liqYSol ->
        val hoy = Clock.System.now().toLocalDateTime(ZonaLima).date
        val litrosHoy = reporte.porDia.firstOrNull { it.etiqueta == hoy.toString() }?.litros?.valor ?: 0.0
        // Ciclo de pago: jueves→miércoles (America/Lima).
        val inicioCiclo = hoy.previousOrSame(DayOfWeek.THURSDAY)
        val finCiclo = inicioCiclo.plus(6, DateTimeUnit.DAY)
        val litrosCiclo = reporte.porDia
            .filter { fila -> runCatching { LocalDate.parse(fila.etiqueta) }.getOrNull()?.let { it in inicioCiclo..finCiclo } == true }
            .sumOf { it.litros.valor }

        val tarifaP = preciosVigentes.firstOrNull { it.concepto == ConceptoPrecio.COMPRA_LECHE && it.vigenteEn(hoy) }
        val tarifa = tarifaP?.valor ?: 0.0

        val ultimaInspeccion = inspeccionesLista.maxByOrNull { it.tomadoEn }
        val calidad = ultimaInspeccion?.dictamen?.clave ?: "Sin inspecciones"
        val rechaza = ultimaInspeccion?.dictamen?.rechazaLote == true

        val (nombre, rutaZona, listaZonas) = datosRuta
        val (rutaNombre, zonaActualId) = rutaZona
        val (liqNeto, ultimaSol) = liqYSol

        Nucleo(
            nombre = nombre,
            litrosHoy = litrosHoy,
            litrosCiclo = litrosCiclo,
            tarifa = tarifa,
            calidad = calidad,
            calidadRechaza = rechaza,
            liquidacionNeto = liqNeto,
            ruta = rutaNombre,
            zonaActualId = zonaActualId,
            zonas = listaZonas,
            ultimaSolicitud = ultimaSol,
        )
    }

    // Entregas del productor con el veredicto de recepción de planta.
    private val entregasFlow = recolecciones.observarPorProductor(productorId)

    val estado: StateFlow<ProductorUiState> =
        combine(nucleo, avisoFlow, avisoImagen, sheet, entregasFlow) { n, aviso, imagen, sh, entregas ->
            val hoy = Clock.System.now().toLocalDateTime(ZonaLima).date
            val inicioCiclo = hoy.previousOrSame(DayOfWeek.THURSDAY)
            val finCiclo = inicioCiclo.plus(6, DateTimeUnit.DAY)
            val enCiclo = entregas.filter { e ->
                e.horaRegistro.toLocalDateTime(ZonaLima).date.let { it in inicioCiclo..finCiclo }
            }
            ProductorUiState(
                cargando = false,
                nombre = n.nombre,
                litrosHoy = n.litrosHoy,
                estadoCalidad = n.calidad,
                calidadRechaza = n.calidadRechaza,
                litrosCiclo = n.litrosCiclo,
                tarifaLitro = n.tarifa,
                pagoProyectado = n.litrosCiclo * n.tarifa,
                ultimaLiquidacionNeto = n.liquidacionNeto,
                rutaAsignada = n.ruta,
                zonaActualId = n.zonaActualId,
                zonas = n.zonas,
                estadoUltimaSolicitud = n.ultimaSolicitud,
                entregas = entregas.take(8).map { e ->
                    EntregaResumen(
                        fecha = e.horaRegistro.toLocalDateTime(ZonaLima).date.toString(),
                        litros = e.litros.valor,
                        estadoRecepcion = e.estadoRecepcion,
                        litrosFaltantes = e.litrosFaltantes.valor,
                    )
                },
                litrosFaltantesCiclo = enCiclo.sumOf { it.litrosFaltantes.valor },
                aviso = aviso,
                avisoImagen = imagen,
                sheet = sh,
            )
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), ProductorUiState())

    fun confirmarAviso() {
        val id = avisoFlow.value?.id ?: return
        viewModelScope.launch { avisos.marcarVisto(id) }  // el Flow lo saca solo del pop-up
    }

    fun abrirSolicitud() { sheet.value = SolicitudSheet() }
    fun cerrarSolicitud() { sheet.value = null }
    fun onZonaSolicitud(id: String) = sheet.update { it?.copy(zonaSeleccionadaId = id, error = null) }
    fun onMotivo(v: String) = sheet.update { it?.copy(motivo = v, error = null) }

    fun enviarSolicitud() {
        val sh = sheet.value ?: return
        val zona = sh.zonaSeleccionadaId
        if (zona == null) {
            sheet.update { it?.copy(error = "Elija una zona") }
            return
        }
        sheet.update { it?.copy(guardando = true, error = null) }
        viewModelScope.launch {
            val r = solicitarCambio(
                SolicitarCambioRutaUseCase.Entrada(
                    productorId = productorId,
                    zonaSolicitadaId = zona,
                    motivo = sh.motivo,
                ),
            )
            when (r) {
                is Resultado.Exito -> {
                    sheet.value = null
                    launch { sincronizarAhora() }
                }
                is Resultado.Fallo -> sheet.update { it?.copy(guardando = false, error = r.error.mensajeUi()) }
            }
        }
    }

}
