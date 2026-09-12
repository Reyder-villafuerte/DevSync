package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.usecase.IniciarJornadaUseCase
import pe.edu.upeu.milkflow.domain.usecase.RegistrarRecoleccionUseCase
import pe.edu.upeu.milkflow.ui.util.coincideSinAcentos
import pe.edu.upeu.milkflow.ui.util.horaLima
import pe.edu.upeu.milkflow.ui.util.mensajeUi

enum class FiltroParada { TODOS, PENDIENTE, COMPLETO }

data class ParadaUi(
    val productorId: String,
    val nombre: String,
    val codigoPadron: String,
    val completo: Boolean,
    val litros: Double?,
    val estadoRecepcion: pe.edu.upeu.milkflow.domain.model.EstadoRecepcion =
        pe.edu.upeu.milkflow.domain.model.EstadoRecepcion.PENDIENTE,
    val litrosFaltantes: Double = 0.0,
)

data class SheetRecoleccion(
    val productorId: String,
    val nombre: String,
    val litrosTexto: String = "",
    val guardando: Boolean = false,
    val error: String? = null,
)

data class AcopiadorUiState(
    val cargando: Boolean = true,
    val rutaNombre: String = "—",
    val hayJornada: Boolean = false,
    val iniciandoJornada: Boolean = false,
    // false mientras la ruta del ámbito no exista en la BD local (catálogo aún
    // no bajado): sin ella no hay padrón que mostrar ni jornada que abrir.
    val puedeIniciarRuta: Boolean = false,
    val kpiLitros: Double = 0.0,
    val kpiSocios: Int = 0,
    val totalParadas: Int = 0,
    /** Hora local de apertura de la jornada, para la cabecera de la ruta. */
    val abiertaDesde: String? = null,
    val paradas: List<ParadaUi> = emptyList(),
    val filtro: FiltroParada = FiltroParada.TODOS,
    val busqueda: String = "",
    val sheet: SheetRecoleccion? = null,
    val avisoJornada: String? = null,
)

@OptIn(ExperimentalCoroutinesApi::class)
class AcopiadorViewModel(
    private val sesion: SesionActiva,
    rutas: RutaRepository,
    zonas: ZonaRepository,
    productores: ProductorRepository,
    private val jornadas: JornadaRepository,
    private val recolecciones: RecoleccionRepository,
    private val iniciarJornada: IniciarJornadaUseCase,
    private val registrarRecoleccion: RegistrarRecoleccionUseCase,
) : ViewModel() {

    private val acopiadorId = sesion.usuarioId
    private val rutaCodigo = (sesion.ambito as? Ambito.Ruta)?.codigo

    private val filtro = MutableStateFlow(FiltroParada.TODOS)
    private val busqueda = MutableStateFlow("")
    private val sheet = MutableStateFlow<SheetRecoleccion?>(null)

    /** Estado del botón "Iniciar ruta" y el motivo si el intento no prosperó. */
    private data class AccionInicio(val enCurso: Boolean = false, val aviso: String? = null)
    private val inicio = MutableStateFlow(AccionInicio())

    // Ruta asignada (por código de ámbito) -> id + nombre.
    private val ruta = rutas.observarTodas().map { lista ->
        lista.firstOrNull { it.codigo == rutaCodigo }
    }

    // Productores de la ruta = padrón filtrado por las zonas de esa ruta.
    private val productoresDeRuta = combine(
        productores.observarPadron(),
        zonas.observarTodas(),
        ruta,
    ) { padron, todasZonas, r ->
        val zonasRuta = todasZonas.filter { it.rutaId == r?.id }.map { it.id }.toSet()
        padron.filter { it.zonaId in zonasRuta }
    }

    private val jornadaActiva: kotlinx.coroutines.flow.Flow<JornadaRuta?> =
        jornadas.observarJornadaActiva(acopiadorId)

    // Recolecciones de la jornada en curso (Flow de SQLDelight: los KPIs se
    // actualizan solos al registrar, sin recargar manualmente).
    private val recoleccionesJornada = jornadaActiva.flatMapLatest { j ->
        if (j == null) flowOf(emptyList()) else recolecciones.observarPorJornada(j.id)
    }

    private data class DatosRuta(
        val ruta: pe.edu.upeu.milkflow.domain.model.Ruta?,
        val padron: List<Productor>,
        val jornada: JornadaRuta?,
        val recolecciones: List<pe.edu.upeu.milkflow.domain.model.Recoleccion>,
    )

    private val datosRuta = combine(
        ruta, productoresDeRuta, jornadaActiva, recoleccionesJornada,
    ) { r, padron, jornada, recos -> DatosRuta(r, padron, jornada, recos) }

    val estado: StateFlow<AcopiadorUiState> =
        combine(datosRuta, filtro, busqueda, sheet, inicio) { d, f, q, sh, ini ->
            val r = d.ruta
            val padron = d.padron
            val jornada = d.jornada
            val recos = d.recolecciones

            val recoPorProductor = recos.associateBy { it.productorId }

            val paradas = padron.map { p ->
                val reco = recoPorProductor[p.id]
                ParadaUi(
                    productorId = p.id,
                    nombre = p.nombreCompleto,
                    codigoPadron = p.codigoPadron,
                    completo = reco != null,
                    litros = reco?.litros?.valor,
                    estadoRecepcion = reco?.estadoRecepcion
                        ?: pe.edu.upeu.milkflow.domain.model.EstadoRecepcion.PENDIENTE,
                    litrosFaltantes = reco?.litrosFaltantes?.valor ?: 0.0,
                )
            }

            val filtradas = paradas
                .filter { par -> par.nombre.coincideSinAcentos(q) || par.codigoPadron.coincideSinAcentos(q) }
                .filter { par ->
                    when (f) {
                        FiltroParada.TODOS -> true
                        FiltroParada.PENDIENTE -> !par.completo
                        FiltroParada.COMPLETO -> par.completo
                    }
                }
                .sortedBy { it.nombre }

            AcopiadorUiState(
                cargando = false,
                rutaNombre = r?.nombre ?: "Ruta ${rutaCodigo ?: "?"}",
                hayJornada = jornada != null,
                iniciandoJornada = ini.enCurso,
                puedeIniciarRuta = r != null,
                kpiLitros = recos.sumOf { it.litros.valor },
                kpiSocios = recos.map { it.productorId }.distinct().size,
                totalParadas = paradas.size,
                abiertaDesde = jornada?.horaInicio?.horaLima(),
                paradas = filtradas,
                filtro = f,
                busqueda = q,
                sheet = sh,
                // Un padrón vacío en pantalla casi siempre significa "todavía no
                // bajó el catálogo", no "no hay proveedores": hay que decirlo.
                avisoJornada = when {
                    ini.aviso != null -> ini.aviso
                    rutaCodigo == null ->
                        "Su usuario no tiene una ruta asignada. Comuníquese con la cooperativa."
                    r == null ->
                        "Aún no se ha descargado su ruta. Pulse «Sincronizar» y vuelva a intentarlo."
                    padron.isEmpty() ->
                        "Su ruta no tiene proveedores descargados. Pulse «Sincronizar» y vuelva a intentarlo."
                    else -> null
                },
            )
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), AcopiadorUiState())

    fun onFiltro(f: FiltroParada) { filtro.value = f }
    fun onBusqueda(q: String) { busqueda.value = q }

    fun iniciarRuta() {
        viewModelScope.launch {
            inicio.value = AccionInicio(enCurso = true)
            var aviso: String? = null
            try {
                // Si la ruta no está en la BD local no hay nada que abrir; el
                // aviso derivado del estado ya explica que falta sincronizar.
                val r = ruta.first()
                if (r != null) {
                    val res = iniciarJornada(acopiadorId, r.id)
                    if (res is Resultado.Fallo) aviso = res.error.mensajeUi()
                }
            } finally {
                inicio.value = AccionInicio(enCurso = false, aviso = aviso)
            }
        }
    }

    fun abrirSheet(parada: ParadaUi) {
        if (parada.completo) return
        sheet.value = SheetRecoleccion(productorId = parada.productorId, nombre = parada.nombre)
    }

    fun onLitrosSheet(v: String) {
        sheet.update { it?.copy(litrosTexto = v.filter { c -> c.isDigit() || c == '.' }, error = null) }
    }

    fun cerrarSheet() { sheet.value = null }

    fun confirmarRecoleccion() {
        val s = sheet.value ?: return
        val litros = s.litrosTexto.toDoubleOrNull()
        if (litros == null) {
            sheet.update { it?.copy(error = "Ingrese los litros (número)") }
            return
        }
        sheet.update { it?.copy(guardando = true, error = null) }
        viewModelScope.launch {
            val r = registrarRecoleccion(
                RegistrarRecoleccionUseCase.Entrada(
                    acopiadorId = acopiadorId,
                    productorId = s.productorId,
                    litros = litros,
                ),
            )
            when (r) {
                is Resultado.Exito -> sheet.value = null   // los KPIs y la lista se refrescan por el Flow
                is Resultado.Fallo -> sheet.update { it?.copy(guardando = false, error = r.error.mensajeUi()) }
            }
        }
    }
}
