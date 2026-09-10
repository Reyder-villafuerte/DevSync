package pe.edu.upeu.milkflow.ui.navigation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import androidx.navigation.NavType
import org.koin.androidx.compose.koinViewModel
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.screens.acopiador.AcopiadorScaffold
import pe.edu.upeu.milkflow.ui.screens.acopiador.CierreRutaScreen
import pe.edu.upeu.milkflow.ui.screens.acopiador.ComprobanteScreen
import pe.edu.upeu.milkflow.ui.screens.conflictos.ConflictosScreen
import pe.edu.upeu.milkflow.ui.screens.login.LoginScreen
import pe.edu.upeu.milkflow.ui.screens.productor.ProductorScreen
import pe.edu.upeu.milkflow.ui.screens.supervisor.HistorialScreen
import pe.edu.upeu.milkflow.ui.screens.supervisor.SupervisorScreen
import pe.edu.upeu.milkflow.ui.theme.Dimens

/**
 * Host de navegación raíz.
 *
 * Decisión de navegación: NO hay un único grafo con guardas. En su lugar se
 * elige el árbol de destinos según `EstadoSesion`; cuando el usuario está
 * autenticado sólo se registran las `composable(...)` de SU rol, de modo que un
 * destino de otro rol literalmente no existe en el `NavController` (no es
 * alcanzable ni por deep-link ni por back stack). Al cerrar sesión, el
 * `observarSesion()` del repositorio emite `null` y esta función se recompone
 * al grafo de login sin navegación manual.
 */
@Composable
fun MilkFlowApp() {
    val gate: SesionGateViewModel = koinViewModel()
    val estado by gate.estado.collectAsState()

    Surface(Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
        when (val e = estado) {
            EstadoSesion.Cargando -> PantallaCentrada { CircularProgressIndicator() }

            EstadoSesion.SinSesion -> {
                val nav = rememberNavController()
                NavHost(nav, startDestination = Destinos.LOGIN) {
                    composable(Destinos.LOGIN) { LoginScreen() }
                }
            }

            is EstadoSesion.RolNoSoportado -> PantallaCentrada {
                Text(
                    "El rol «${e.rol.clave}» opera desde el panel web, no desde la app móvil.",
                    style = MaterialTheme.typography.bodyLarge,
                    textAlign = TextAlign.Center,
                )
                Button(onClick = gate::cerrarSesion, modifier = Modifier.objetivoTactil()) {
                    Text("Cerrar sesión")
                }
            }

            is EstadoSesion.Autenticado -> GrafoPorRol(e.sesion, gate::cerrarSesion)
        }
    }
}

@Composable
private fun GrafoPorRol(sesion: SesionActiva, onCerrarSesion: () -> Unit) {
    val nav = rememberNavController()
    val irAConflictos = { nav.navigate(Destinos.CONFLICTOS) }

    when (sesion.rol) {
        RolUsuario.ACOPIADOR -> NavHost(nav, startDestination = Destinos.ACOPIADOR_HOME) {
            composable(Destinos.ACOPIADOR_HOME) {
                AcopiadorScaffold(
                    sesion = sesion,
                    onAbrirCierre = { nav.navigate(Destinos.ACOPIADOR_CIERRE) },
                    onAbrirConflictos = irAConflictos,
                    onCerrarSesion = onCerrarSesion,
                )
            }
            composable(Destinos.ACOPIADOR_CIERRE) {
                CierreRutaScreen(
                    sesion = sesion,
                    onVolver = { nav.popBackStack() },
                    onComprobante = { jornadaId ->
                        nav.navigate("${Destinos.ACOPIADOR_COMPROBANTE}/$jornadaId") {
                            popUpTo(Destinos.ACOPIADOR_HOME)
                        }
                    },
                )
            }
            composable(
                "${Destinos.ACOPIADOR_COMPROBANTE}/{jornadaId}",
                arguments = listOf(navArgument("jornadaId") { type = NavType.StringType }),
            ) { entrada ->
                ComprobanteScreen(
                    jornadaId = entrada.arguments?.getString("jornadaId").orEmpty(),
                    onVolver = { nav.popBackStack(Destinos.ACOPIADOR_HOME, false) },
                    onAbrirConflictos = irAConflictos,
                )
            }
            composable(Destinos.CONFLICTOS) { ConflictosScreen(onVolver = { nav.popBackStack() }) }
        }

        RolUsuario.SUPERVISOR_CALIDAD -> NavHost(nav, startDestination = Destinos.SUPERVISOR_INSPECCION) {
            composable(Destinos.SUPERVISOR_INSPECCION) {
                SupervisorScreen(
                    sesion = sesion,
                    onAbrirHistorial = { nav.navigate(Destinos.SUPERVISOR_HISTORIAL) },
                    onAbrirConflictos = irAConflictos,
                    onCerrarSesion = onCerrarSesion,
                )
            }
            composable(Destinos.SUPERVISOR_HISTORIAL) {
                HistorialScreen(onVolver = { nav.popBackStack() })
            }
            composable(Destinos.CONFLICTOS) { ConflictosScreen(onVolver = { nav.popBackStack() }) }
        }

        RolUsuario.PRODUCTOR -> NavHost(nav, startDestination = Destinos.PRODUCTOR_DASHBOARD) {
            composable(Destinos.PRODUCTOR_DASHBOARD) {
                ProductorScreen(
                    sesion = sesion,
                    onAbrirConflictos = irAConflictos,
                    onCerrarSesion = onCerrarSesion,
                )
            }
            composable(Destinos.CONFLICTOS) { ConflictosScreen(onVolver = { nav.popBackStack() }) }
        }

        else -> PantallaCentrada {
            Text("Rol no soportado en la app móvil.")
            Button(onClick = onCerrarSesion, modifier = Modifier.objetivoTactil()) { Text("Cerrar sesión") }
        }
    }
}

@Composable
private fun PantallaCentrada(contenido: @Composable () -> Unit) {
    Column(
        Modifier.fillMaxSize().padding(Dimens.EspacioL),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(16.dp, Alignment.CenterVertically),
    ) { contenido() }
}
