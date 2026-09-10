package pe.edu.upeu.milkflow.ui.components

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.sync.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEstadoSincronizacionUseCase
import pe.edu.upeu.milkflow.domain.usecase.SincronizarAhoraUseCase

/**
 * Estado de sincronización global. Es un único ViewModel compartido por el
 * `IndicadorSync` de las tres pantallas: expone el `StateFlow` del SyncManager
 * (fuente de verdad) y permite disparar una sincronización manual sin bloquear.
 */
class SyncViewModel(
    obtenerEstado: ObtenerEstadoSincronizacionUseCase,
    private val sincronizarAhora: SincronizarAhoraUseCase,
) : ViewModel() {

    val estado: StateFlow<EstadoSincronizacion> = obtenerEstado()

    fun sincronizar() {
        // Nunca bloquea la UI; el SyncManager es reentrante-seguro.
        viewModelScope.launch { sincronizarAhora() }
    }
}
