package pe.edu.upeu.milkflow.ui.screens.conflictos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.sync.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.usecase.SincronizarAhoraUseCase
import pe.edu.upeu.milkflow.ui.components.EstadoUi
import pe.edu.upeu.milkflow.ui.util.fechaHoraLima

data class ConflictoItem(
    val id: Long,
    val entidad: String,
    val operacion: String,
    val idRegistro: String,
    val motivo: String,
    val cuando: String,
)

/**
 * Conflictos de sincronización expuestos al usuario (NO silenciados): son las
 * operaciones del outbox en estado CONFLICTO. Se puede reintentar (útil solo si
 * la causa ya se corrigió en el servidor) o descartar el envío; la resolución
 * fina de los datos la hace administración desde el panel web.
 */
class ConflictosViewModel(
    private val sincronizacion: SincronizacionRepository,
    private val sincronizarAhora: SincronizarAhoraUseCase,
) : ViewModel() {

    private val reintentando = MutableStateFlow(false)

    val estado: StateFlow<EstadoUi<List<ConflictoItem>>> =
        combine(sincronizacion.observarConflictos(), reintentando) { conflictos, _ ->
            if (conflictos.isEmpty()) {
                EstadoUi.Vacio("No hay conflictos de sincronización.")
            } else {
                EstadoUi.Contenido(
                    conflictos.map {
                        ConflictoItem(
                            id = it.idLocal,
                            entidad = it.tabla,
                            operacion = it.operacion.name,
                            idRegistro = it.idRegistro,
                            motivo = it.ultimoError ?: "Rechazado por el servidor",
                            cuando = it.creadoEn.fechaHoraLima(),
                        )
                    },
                )
            }
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), EstadoUi.Cargando)

    val reintentandoFlow: StateFlow<Boolean> = reintentando

    /**
     * Saca la operación del outbox. Es la única salida para un rechazo
     * permanente (una recolección que el servidor nunca va a aceptar): sin
     * esto la fila se queda en la pantalla para siempre, porque "reintentar"
     * solo recorre las operaciones PENDIENTES. El dato local no se borra: lo
     * que se descarta es el intento de subirlo.
     */
    fun descartar(idLocal: Long) {
        viewModelScope.launch { sincronizacion.descartar(idLocal) }
    }

    fun reintentar() {
        reintentando.value = true
        viewModelScope.launch {
            try {
                sincronizarAhora()
            } finally {
                reintentando.value = false
            }
        }
    }
}
