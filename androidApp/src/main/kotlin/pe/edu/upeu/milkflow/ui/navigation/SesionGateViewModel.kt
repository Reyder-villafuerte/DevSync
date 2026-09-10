package pe.edu.upeu.milkflow.ui.navigation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SesionRepository

/** Estado de sesión que decide qué grafo de navegación se muestra. */
sealed interface EstadoSesion {
    data object Cargando : EstadoSesion
    data object SinSesion : EstadoSesion
    data class Autenticado(val sesion: SesionActiva) : EstadoSesion

    /** Rol válido en el backend pero sin pantalla en la app móvil (planta/admin). */
    data class RolNoSoportado(val rol: RolUsuario) : EstadoSesion
}

private val ROLES_MOVIL = setOf(
    RolUsuario.ACOPIADOR, RolUsuario.SUPERVISOR_CALIDAD, RolUsuario.PRODUCTOR,
)

class SesionGateViewModel(
    private val sesiones: SesionRepository,
) : ViewModel() {

    val estado: StateFlow<EstadoSesion> =
        sesiones.observarSesion()
            .map { sesion ->
                when {
                    sesion == null -> EstadoSesion.SinSesion
                    sesion.rol in ROLES_MOVIL -> EstadoSesion.Autenticado(sesion)
                    else -> EstadoSesion.RolNoSoportado(sesion.rol)
                }
            }
            .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), EstadoSesion.Cargando)

    fun cerrarSesion() {
        viewModelScope.launch { sesiones.cerrarSesion() }
    }
}
