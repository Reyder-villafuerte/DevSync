package pe.edu.upeu.milkflow.presentation.navigation

import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import org.koin.compose.koinInject
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.presentation.acopiador.AcopiadoresScreen
import pe.edu.upeu.milkflow.presentation.acopiador.AcopiadorViewModel
import pe.edu.upeu.milkflow.presentation.acopiador.RegistrarAcopiadorScreen
import pe.edu.upeu.milkflow.presentation.auditoria.AuditoriaScreen
import pe.edu.upeu.milkflow.presentation.auditoria.AuditoriaViewModel
import pe.edu.upeu.milkflow.presentation.calidad.CalidadScreen
import pe.edu.upeu.milkflow.presentation.calidad.CalidadViewModel
import pe.edu.upeu.milkflow.presentation.calidad.RegistrarPruebaCalidadScreen
import pe.edu.upeu.milkflow.presentation.components.MilkFlowBottomBar
import pe.edu.upeu.milkflow.presentation.components.MilkFlowBottomItem
import pe.edu.upeu.milkflow.presentation.components.MilkFlowTopBar
import pe.edu.upeu.milkflow.presentation.consultas.ConsultarEntregasProductorScreen
import pe.edu.upeu.milkflow.presentation.consultas.ConsultaViewModel
import pe.edu.upeu.milkflow.presentation.consultas.ResumenViewModel
import pe.edu.upeu.milkflow.presentation.consultas.ResumenProductorScreen
import pe.edu.upeu.milkflow.presentation.entrega.EntregasScreen
import pe.edu.upeu.milkflow.presentation.entrega.EntregaViewModel
import pe.edu.upeu.milkflow.presentation.entrega.RegistrarEntregaScreen
import org.koin.compose.viewmodel.koinViewModel
import pe.edu.upeu.milkflow.presentation.inicio.InicioNavigation
import pe.edu.upeu.milkflow.presentation.inicio.InicioScreen
import pe.edu.upeu.milkflow.presentation.inicio.InicioUiEvent
import pe.edu.upeu.milkflow.presentation.inicio.InicioViewModel
import pe.edu.upeu.milkflow.presentation.login.LoginScreen
import pe.edu.upeu.milkflow.presentation.login.LoginSubmission
import pe.edu.upeu.milkflow.presentation.login.LoginViewModel
import pe.edu.upeu.milkflow.presentation.registro.RegistroScreen
import pe.edu.upeu.milkflow.presentation.registro.RegistroViewModel
import pe.edu.upeu.milkflow.presentation.perfil.PerfilScreen
import pe.edu.upeu.milkflow.presentation.perfil.PerfilUiEvent
import pe.edu.upeu.milkflow.presentation.perfil.PerfilViewModel
import pe.edu.upeu.milkflow.presentation.productor.ActualizarProductorScreen
import pe.edu.upeu.milkflow.presentation.productor.ProductoresScreen
import pe.edu.upeu.milkflow.presentation.productor.ProductorViewModel
import pe.edu.upeu.milkflow.presentation.reportes.ReportesScreen
import pe.edu.upeu.milkflow.presentation.reportes.ReporteViewModel
import pe.edu.upeu.milkflow.presentation.sincronizacion.SincronizacionScreen
import pe.edu.upeu.milkflow.presentation.sincronizacion.SincronizacionViewModel
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario
import pe.edu.upeu.milkflow.presentation.usuarios.UsuariosScreen
import pe.edu.upeu.milkflow.presentation.usuarios.UsuariosViewModel

@Composable
fun MilkFlowNavigation(
    modifier: Modifier = Modifier,
    navController: NavHostController = rememberNavController(),
) {
    val sesionUsuario = koinInject<SesionUsuario>()
    val usuarioSesion by sesionUsuario.usuario.collectAsState()
    
    val bottomItems = remember(usuarioSesion?.rol) {
        val rol = usuarioSesion?.rol
        listOfNotNull(
            MilkFlowBottomItem(AppDestination.Inicio.route, "Inicio", "⌂"),
            if (rol != RolUsuario.DESPACHO_QUESO && rol != RolUsuario.SUPERVISOR) {
                MilkFlowBottomItem(AppDestination.Entregas.route, "Entregas", "≡")
            } else null,
            if (rol == RolUsuario.ADMINISTRADORA || rol == RolUsuario.JEFE_PRODUCCION) {
                MilkFlowBottomItem(AppDestination.Reportes.route, "Reportes", "▥")
            } else null,
            if (rol != RolUsuario.DESPACHO_QUESO) {
                MilkFlowBottomItem(AppDestination.Sincronizacion.route, "Sincronización", "↻")
            } else null,
            MilkFlowBottomItem(AppDestination.Perfil.route, "Perfil", "●"),
        )
    }

    val entregaViewModel = koinViewModel<EntregaViewModel>()
    val calidadViewModel = koinViewModel<CalidadViewModel>()
    val backStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = backStackEntry?.destination?.route
    val destination = AppDestination.fromRoute(currentRoute)
    val showTopBar = destination != null && 
            destination != AppDestination.Login && 
            destination != AppDestination.Registro
    val showBottomBar = destination?.isBottomDestination == true

    LaunchedEffect(usuarioSesion?.id, currentRoute) {
        if (usuarioSesion == null && 
            currentRoute != AppDestination.Login.route && 
            currentRoute != AppDestination.Registro.route) {
            navController.navigate(AppDestination.Login.route) {
                popUpTo(navController.graph.id) { inclusive = true }
                launchSingleTop = true
            }
        }
    }

    Scaffold(
        modifier = modifier,
        topBar = {
            if (showTopBar) {
                MilkFlowTopBar(
                    title = destination.title,
                    canNavigateBack = !destination.isBottomDestination,
                    onNavigateBack = { navController.popBackStack() },
                )
            }
        },
        bottomBar = {
            if (showBottomBar) {
                MilkFlowBottomBar(
                    items = bottomItems,
                    currentRoute = currentRoute,
                    onSelect = { item -> navController.navigateBottomDestination(item.route) },
                )
            }
        },
    ) { contentPadding ->
        NavHost(
            navController = navController,
            startDestination = AppDestination.Login.route,
            modifier = Modifier.padding(contentPadding),
        ) {
            composable(AppDestination.Login.route) {
                val viewModel = koinViewModel<LoginViewModel>()
                val state by viewModel.uiState.collectAsState()
                val authenticatedUser = (state.submission as? LoginSubmission.Success)?.usuario

                LaunchedEffect(authenticatedUser?.id) {
                    if (authenticatedUser != null) {
                        navController.navigate(AppDestination.Inicio.route) {
                            popUpTo(AppDestination.Login.route) { inclusive = true }
                            launchSingleTop = true
                        }
                    }
                }
                LoginScreen(
                    state = state,
                    onEvent = viewModel::onEvent,
                    onNavigateToRegistro = {
                        navController.navigate(AppDestination.Registro.route)
                    }
                )
            }
            composable(AppDestination.Registro.route) {
                val viewModel = koinViewModel<RegistroViewModel>()
                val state by viewModel.uiState.collectAsState()
                RegistroScreen(
                    state = state,
                    onEvent = viewModel::onEvent,
                    onNavigateBack = {
                        navController.popBackStack()
                    }
                )
            }
            composable(AppDestination.Inicio.route) {
                val viewModel = koinViewModel<InicioViewModel>()
                val state by viewModel.uiState.collectAsState()

                LaunchedEffect(state.navigation) {
                    val target = state.navigation ?: return@LaunchedEffect
                    navController.navigate(target.toDestination().route)
                    viewModel.onEvent(InicioUiEvent.NavigationHandled)
                }
                InicioScreen(
                    state = state,
                    onEvent = viewModel::onEvent,
                )
            }
            composable(AppDestination.Entregas.route) {
                val state by entregaViewModel.uiState.collectAsState()
                EntregasScreen(
                    state = state,
                    onEvent = entregaViewModel::onEvent,
                    onRegistrarEntrega = {
                        navController.navigate(AppDestination.RegistrarEntrega.route)
                    },
                )
            }
            composable(AppDestination.RegistrarEntrega.route) {
                val state by entregaViewModel.uiState.collectAsState()
                RegistrarEntregaScreen(state = state, onEvent = entregaViewModel::onEvent)
            }
            composable(AppDestination.Productores.route) {
                val viewModel = koinViewModel<ProductorViewModel>()
                val state by viewModel.uiState.collectAsState()
                ProductoresScreen(
                    state = state,
                    onEvent = viewModel::onEvent,
                    onActualizarProductor = {
                        navController.navigate(AppDestination.ActualizarProductor.route)
                    },
                )
            }
            composable(AppDestination.ActualizarProductor.route) {
                val viewModel = koinViewModel<ProductorViewModel>()
                val state by viewModel.uiState.collectAsState()
                ActualizarProductorScreen(state = state, onEvent = viewModel::onEvent)
            }
            composable(AppDestination.Acopiadores.route) {
                val viewModel = koinViewModel<AcopiadorViewModel>()
                val state by viewModel.uiState.collectAsState()
                AcopiadoresScreen(
                    state = state,
                    onEvent = viewModel::onEvent,
                    onRegistrarAcopiador = {
                        navController.navigate(AppDestination.RegistrarAcopiador.route)
                    },
                )
            }
            composable(AppDestination.RegistrarAcopiador.route) {
                val viewModel = koinViewModel<AcopiadorViewModel>()
                val state by viewModel.uiState.collectAsState()
                RegistrarAcopiadorScreen(state = state, onEvent = viewModel::onEvent)
            }
            composable(AppDestination.Calidad.route) {
                val state by calidadViewModel.uiState.collectAsState()
                CalidadScreen(
                    state = state,
                    onEvent = calidadViewModel::onEvent,
                    onRegistrarPrueba = {
                        navController.navigate(AppDestination.RegistrarPruebaCalidad.route)
                    },
                )
            }
            composable(AppDestination.RegistrarPruebaCalidad.route) {
                val state by calidadViewModel.uiState.collectAsState()
                RegistrarPruebaCalidadScreen(state = state, onEvent = calidadViewModel::onEvent)
            }
            composable(AppDestination.ConsultarEntregasProductor.route) {
                val viewModel = koinViewModel<ConsultaViewModel>()
                val state by viewModel.uiState.collectAsState()
                ConsultarEntregasProductorScreen(
                    state = state,
                    onEvent = viewModel::onEvent,
                    onVerResumen = {
                        navController.navigate(AppDestination.ResumenProductor.route)
                    },
                )
            }
            composable(AppDestination.ResumenProductor.route) {
                val viewModel = koinViewModel<ResumenViewModel>()
                val state by viewModel.uiState.collectAsState()
                ResumenProductorScreen(state = state, onEvent = viewModel::onEvent)
            }
            composable(AppDestination.Reportes.route) {
                val viewModel = koinViewModel<ReporteViewModel>()
                val state by viewModel.uiState.collectAsState()
                ReportesScreen(state = state, onEvent = viewModel::onEvent)
            }
            composable(AppDestination.Sincronizacion.route) {
                val viewModel = koinViewModel<SincronizacionViewModel>()
                val state by viewModel.uiState.collectAsState()
                SincronizacionScreen(state = state, onEvent = viewModel::onEvent)
            }
            composable(AppDestination.Usuarios.route) {
                val viewModel = koinViewModel<UsuariosViewModel>()
                val state by viewModel.uiState.collectAsState()
                UsuariosScreen(state = state, onEvent = viewModel::onEvent)
            }
            composable(AppDestination.Auditoria.route) {
                val viewModel = koinViewModel<AuditoriaViewModel>()
                val state by viewModel.uiState.collectAsState()
                AuditoriaScreen(state = state, onEvent = viewModel::onEvent)
            }
            composable(AppDestination.Perfil.route) {
                val viewModel = koinViewModel<PerfilViewModel>()
                val state by viewModel.uiState.collectAsState()
                LaunchedEffect(state.sesionActiva, state.cierreProcesado) {
                    if (!state.sesionActiva && !state.cierreProcesado) {
                        viewModel.onEvent(PerfilUiEvent.CierreProcesado)
                        navController.navigate(AppDestination.Login.route) {
                            popUpTo(navController.graph.id) { inclusive = true }
                            launchSingleTop = true
                        }
                    }
                }
                PerfilScreen(state = state, onEvent = viewModel::onEvent)
            }
        }
    }
}

private fun NavHostController.navigateBottomDestination(route: String) {
    navigate(route) {
        popUpTo(AppDestination.Inicio.route) { saveState = true }
        launchSingleTop = true
        restoreState = true
    }
}

private fun InicioNavigation.toDestination(): AppDestination = when (this) {
    InicioNavigation.REGISTRAR_ENTREGA -> AppDestination.RegistrarEntrega
    InicioNavigation.PRODUCTORES -> AppDestination.Productores
    InicioNavigation.ACOPIADORES -> AppDestination.Acopiadores
    InicioNavigation.CALIDAD -> AppDestination.Calidad
    InicioNavigation.REPORTES -> AppDestination.Reportes
    InicioNavigation.CONSULTAS -> AppDestination.ConsultarEntregasProductor
    InicioNavigation.USUARIOS -> AppDestination.Usuarios
    InicioNavigation.AUDITORIA -> AppDestination.Auditoria
}
