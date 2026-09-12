package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository
import pe.edu.upeu.milkflow.domain.usecase.CerrarJornadaUseCase
import pe.edu.upeu.milkflow.ui.util.mensajeUi

data class CierreUiState(
    val cargando: Boolean = true,
    val hayJornada: Boolean = false,
    val jornadaId: String? = null,
    val litros: Double = 0.0,
    val socios: Int = 0,
    val paradasPendientes: Int = 0,
    val cerrando: Boolean = false,
    val error: String? = null,
    /** id de la jornada ya cerrada -> la pantalla navega al comprobante. */
    val cerrada: String? = null,
)

@OptIn(ExperimentalCoroutinesApi::class)
class CierreRutaViewModel(
    sesion: SesionActiva,
    rutas: RutaRepository,
    zonas: ZonaRepository,
    productores: ProductorRepository,
    recolecciones: RecoleccionRepository,
    jornadas: JornadaRepository,
    private val cerrarJornada: CerrarJornadaUseCase,
) : ViewModel() {

    private val acopiadorId = sesion.usuarioId
    private val rutaCodigo = (sesion.ambito as? Ambito.Ruta)?.codigo

    private data class Proceso(val cerrando: Boolean = false, val error: String? = null, val cerrada: String? = null)

    private val proceso = MutableStateFlow(Proceso())

    private val jornada: Flow<JornadaRuta?> = jornadas.observarJornadaActiva(acopiadorId)

    private val idsProductoresRuta: Flow<Set<String>> = combine(
        productores.observarPadron(), zonas.observarTodas(), rutas.observarTodas(),
    ) { padron, zs, rs ->
        val rid = rs.firstOrNull { it.codigo == rutaCodigo }?.id
        val zonasRuta = zs.filter { it.rutaId == rid }.map { it.id }.toSet()
        padron.filter { it.zonaId in zonasRuta }.map { it.id }.toSet()
    }

    private val recoleccionesJornada: Flow<List<Recoleccion>> = jornada.flatMapLatest { j ->
        if (j == null) flowOf(emptyList()) else recolecciones.observarPorJornada(j.id)
    }

    private data class Resumen(
        val jornadaId: String?, val hayJornada: Boolean,
        val litros: Double, val socios: Int, val pendientes: Int,
    )

    private val resumen: Flow<Resumen> = combine(jornada, idsProductoresRuta, recoleccionesJornada) { j, idsRuta, recos ->
        val atendidos = recos.map { it.productorId }.toSet()
        Resumen(
            jornadaId = j?.id,
            hayJornada = j != null,
            litros = recos.sumOf { it.litros.valor },
            socios = atendidos.size,
            pendientes = idsRuta.count { it !in atendidos },
        )
    }

    val estado: StateFlow<CierreUiState> =
        combine(resumen, proceso) { r, p ->
            CierreUiState(
                cargando = false,
                hayJornada = r.hayJornada,
                jornadaId = r.jornadaId,
                litros = r.litros,
                socios = r.socios,
                paradasPendientes = r.pendientes,
                cerrando = p.cerrando,
                error = p.error,
                cerrada = p.cerrada,
            )
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), CierreUiState())

    fun confirmarCierre() {
        val id = estado.value.jornadaId ?: return
        proceso.update { it.copy(cerrando = true, error = null) }
        viewModelScope.launch {
            val r = cerrarJornada(CerrarJornadaUseCase.Entrada(jornadaId = id))
            when (r) {
                is Resultado.Exito -> proceso.update { it.copy(cerrando = false, cerrada = r.valor.id) }
                is Resultado.Fallo -> proceso.update { it.copy(cerrando = false, error = r.error.mensajeUi()) }
            }
        }
    }
}
