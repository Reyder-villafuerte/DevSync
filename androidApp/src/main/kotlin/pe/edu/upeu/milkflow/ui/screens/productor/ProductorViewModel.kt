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
import pe.edu.upeu.milkflow.domain.model.DictamenCalidad
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

/** Ficha del socio en el padrón (un Triple ya no daba para tanto campo). */
private data class DatosPadron(
    val nombre: String,
    val codigoPadron: String,
    val dni: String,
    val telefono: String?,
    val estadoPadron: String,
    val fechaIngreso: String,
    val zonaNombre: String,
    val ruta: String,
    val zonaActualId: String?,
    val zonas: List<ZonaOpcion>,
)

/** Corte del reporte de acopio del socio. */
enum class PeriodoProductor(val etiqueta: String) { DIA("Día"), SEMANA("Semana"), MES("Mes") }

/** Una liquidación semanal vista por el socio. */
data class PagoResumen(
    val etiqueta: String,
    val litros: Double,
    val precioLitro: Double,
    val tarifaDegradada: Boolean,
    val descuentos: Double,
    val neto: Double,
    val estado: String,
)

/** Fila del reporte: un día, una semana o un mes. */
data class FilaReporte(val etiqueta: String, val litros: Double, val entregas: Int)

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

/** Cuántas inspecciones del socio terminaron en cada dictamen. */
data class ConteoDictamen(val dictamen: DictamenCalidad, val cantidad: Int)

data class ProductorUiState(
    val cargando: Boolean = true,
    val nombre: String = "",
    /** Código de padrón: es el "ID de proveedor" con el que lo llama la planta. */
    val codigoPadron: String = "",
    val entregasCiclo: Int = 0,
    val calidad: List<ConteoDictamen> = emptyList(),
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
    // --- Pagos ---
    val pagos: List<PagoResumen> = emptyList(),
    val totalPendiente: Double = 0.0,
    val ultimoPagoEtiqueta: String? = null,
    // --- Reportes ---
    val periodo: PeriodoProductor = PeriodoProductor.SEMANA,
    val filasReporte: List<FilaReporte> = emptyList(),
    val litrosPeriodo: Double = 0.0,
    val entregasPeriodo: Int = 0,
    val promedioDiario: Double = 0.0,
    // --- Perfil ---
    val dni: String = "",
    val telefono: String? = null,
    val estadoPadron: String = "",
    val fechaIngreso: String = "",
    val zonaNombre: String = "—",
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
    private val periodo = MutableStateFlow(PeriodoProductor.SEMANA)

    /** El aviso y su imagen viajan juntos: el combine de estado admite 5 flujos. */
    private val avisoConImagen = combine(avisoFlow, avisoImagen) { a, i -> a to i }

    init {
        // El aviso obligatorio se observa y, si trae imagen, se descarga sin librería.
        obtenerAviso()
            .onEach { a ->
                avisoFlow.value = a
                avisoImagen.value = if (a?.imagenUrl != null) CargadorImagenRemota.cargar(a.imagenUrl) else null
            }
            .launchIn(viewModelScope)
    }

    private val ordenPorGravedad = listOf(
        DictamenCalidad.APROBADO,
        DictamenCalidad.ADVERTENCIA_AGUA,
        DictamenCalidad.DESCUENTO_RETIRO_AGUA,
        DictamenCalidad.RECHAZADO_ACIDEZ,
        DictamenCalidad.EXPULSION_AGUA,
    )

    private data class Nucleo(
        val padron: DatosPadron,
        val repartoCalidad: List<ConteoDictamen>,
        val litrosHoy: Double,
        val litrosCiclo: Double,
        val tarifa: Double,
        val calidad: String,
        val calidadRechaza: Boolean,
        val liquidacionNeto: Double?,
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
            DatosPadron(
                nombre = p?.nombreCompleto ?: "",
                codigoPadron = p?.codigoPadron ?: "",
                dni = p?.dni?.valor ?: "",
                telefono = p?.telefono,
                estadoPadron = p?.estado?.clave ?: "",
                fechaIngreso = p?.fechaIngreso?.toString() ?: "",
                zonaNombre = zona?.nombre ?: "—",
                ruta = ruta?.nombre ?: "—",
                zonaActualId = p?.zonaId,
                zonas = zs.map { ZonaOpcion(it.id, it.nombre) },
            )
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

        val (liqNeto, ultimaSol) = liqYSol

        // Reparto de dictámenes para la dona de calidad: de mejor a peor (el
        // orden del enum no es el de gravedad) y sin lo que no ocurrió.
        val reparto = ordenPorGravedad
            .map { d -> ConteoDictamen(d, inspeccionesLista.count { it.dictamen == d }) }
            .filter { it.cantidad > 0 }

        Nucleo(
            padron = datosRuta,
            repartoCalidad = reparto,
            litrosHoy = litrosHoy,
            litrosCiclo = litrosCiclo,
            tarifa = tarifa,
            calidad = calidad,
            calidadRechaza = rechaza,
            liquidacionNeto = liqNeto,
            ultimaSolicitud = ultimaSol,
        )
    }

    // Entregas del productor con el veredicto de recepción de planta.
    private val entregasFlow = recolecciones.observarPorProductor(productorId)

    private data class Extras(
        val pagos: List<PagoResumen>,
        val totalPendiente: Double,
        val ultimoPagoEtiqueta: String?,
        val periodo: PeriodoProductor,
        val filas: List<FilaReporte>,
    )

    /** Pagos y reporte por periodo: alimentan las pestañas Pagos y Reportes. */
    private val extras = combine(
        liquidaciones.observarPorProductor(productorId),
        obtenerReporte(productorId),
        periodo,
    ) { liqs, reporte, p ->
        val ordenadas = liqs.sortedByDescending { it.updatedAt }
        val pagos = ordenadas.map { l ->
            PagoResumen(
                etiqueta = l.semanaPagoId.take(8),
                litros = l.litrosTotales.valor,
                precioLitro = l.precioLitroAplicado,
                tarifaDegradada = l.tarifaDegradada,
                descuentos = l.totalDescuentos.valor,
                neto = l.montoNeto.valor,
                estado = l.estado,
            )
        }
        val filas = when (p) {
            PeriodoProductor.DIA -> reporte.porDia
            PeriodoProductor.SEMANA -> reporte.porSemana
            PeriodoProductor.MES -> reporte.porMes
        }.sortedByDescending { it.etiqueta }
            .map { FilaReporte(it.etiqueta, it.litros.valor, it.recolecciones) }

        Extras(
            pagos = pagos,
            // "Pendiente" = liquidada pero todavía no pagada por la cooperativa.
            totalPendiente = ordenadas.filter { it.estado != "pagada" }.sumOf { it.montoNeto.valor },
            ultimoPagoEtiqueta = ordenadas.firstOrNull { it.estado == "pagada" }?.semanaPagoId?.take(8),
            periodo = p,
            filas = filas,
        )
    }

    val estado: StateFlow<ProductorUiState> =
        combine(nucleo, extras, avisoConImagen, sheet, entregasFlow) { n, x, avisoYImagen, sh, entregas ->
            val (aviso, imagen) = avisoYImagen
            val hoy = Clock.System.now().toLocalDateTime(ZonaLima).date
            val inicioCiclo = hoy.previousOrSame(DayOfWeek.THURSDAY)
            val finCiclo = inicioCiclo.plus(6, DateTimeUnit.DAY)
            val enCiclo = entregas.filter { e ->
                e.horaRegistro.toLocalDateTime(ZonaLima).date.let { it in inicioCiclo..finCiclo }
            }
            val diasConEntrega = x.filas.size.coerceAtLeast(1)
            ProductorUiState(
                cargando = false,
                nombre = n.padron.nombre,
                codigoPadron = n.padron.codigoPadron,
                dni = n.padron.dni,
                telefono = n.padron.telefono,
                estadoPadron = n.padron.estadoPadron,
                fechaIngreso = n.padron.fechaIngreso,
                zonaNombre = n.padron.zonaNombre,
                pagos = x.pagos,
                totalPendiente = x.totalPendiente,
                ultimoPagoEtiqueta = x.ultimoPagoEtiqueta,
                periodo = x.periodo,
                filasReporte = x.filas,
                litrosPeriodo = x.filas.sumOf { it.litros },
                entregasPeriodo = x.filas.sumOf { it.entregas },
                promedioDiario = x.filas.sumOf { it.litros } / diasConEntrega,
                entregasCiclo = enCiclo.size,
                calidad = n.repartoCalidad,
                litrosHoy = n.litrosHoy,
                estadoCalidad = n.calidad,
                calidadRechaza = n.calidadRechaza,
                litrosCiclo = n.litrosCiclo,
                tarifaLitro = n.tarifa,
                pagoProyectado = n.litrosCiclo * n.tarifa,
                ultimaLiquidacionNeto = n.liquidacionNeto,
                rutaAsignada = n.padron.ruta,
                zonaActualId = n.padron.zonaActualId,
                zonas = n.padron.zonas,
                estadoUltimaSolicitud = n.ultimaSolicitud,
                entregas = entregas.map { e ->
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

    fun onPeriodo(p: PeriodoProductor) { periodo.value = p }

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
