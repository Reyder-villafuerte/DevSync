package com.example.milkflowmovil.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DrawerValue
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalDrawerSheet
import androidx.compose.material3.ModalNavigationDrawer
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.material3.rememberDrawerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.di.Contenedor
import com.example.milkflowmovil.domain.model.Rol
import com.example.milkflowmovil.presentation.components.MarcaHuata
import com.example.milkflowmovil.presentation.navigation.Navegador
import com.example.milkflowmovil.presentation.navigation.Pantalla
import com.example.milkflowmovil.presentation.navigation.menu
import com.example.milkflowmovil.presentation.screens.PantallaAcopio
import com.example.milkflowmovil.presentation.screens.PantallaAutorizarPagos
import com.example.milkflowmovil.presentation.screens.PantallaAvisos
import com.example.milkflowmovil.presentation.screens.PantallaCalidad
import com.example.milkflowmovil.presentation.screens.PantallaCaudalimetro
import com.example.milkflowmovil.presentation.screens.PantallaFinanzas
import com.example.milkflowmovil.presentation.screens.PantallaHistorialRutas
import com.example.milkflowmovil.presentation.screens.PantallaHistorialSobres
import com.example.milkflowmovil.presentation.screens.PantallaLogin
import com.example.milkflowmovil.presentation.screens.PantallaMiAcopio
import com.example.milkflowmovil.presentation.screens.PantallaMiCalidad
import com.example.milkflowmovil.presentation.screens.PantallaMiZona
import com.example.milkflowmovil.presentation.screens.PantallaMisDescuentos
import com.example.milkflowmovil.presentation.screens.PantallaMisPagos
import com.example.milkflowmovil.presentation.screens.PantallaNuevaVenta
import com.example.milkflowmovil.presentation.screens.PantallaPanel
import com.example.milkflowmovil.presentation.screens.PantallaRecibo
import com.example.milkflowmovil.presentation.screens.PantallaRecibos
import com.example.milkflowmovil.presentation.screens.PantallaSincronizacion
import com.example.milkflowmovil.presentation.screens.PantallaSobresRuta
import com.example.milkflowmovil.presentation.screens.PantallaSolicitudesZona
import com.example.milkflowmovil.presentation.screens.PantallaTarifas
import com.example.milkflowmovil.presentation.screens.PantallaVentas
import com.example.milkflowmovil.presentation.screens.PantallaZonas
import com.example.milkflowmovil.presentation.theme.SelectorTema
import com.example.milkflowmovil.presentation.theme.TemaMilkFlow
import com.example.milkflowmovil.presentation.theme.coloresMilkFlow
import com.example.milkflowmovil.presentation.viewmodel.SesionViewModel
import com.example.milkflowmovil.presentation.viewmodel.SincronizacionViewModel
import kotlinx.coroutines.launch

/**
 * Raíz de la aplicación.
 *
 * Solo decide tres cosas: si hay sesión, qué menú corresponde al rol y qué
 * pantalla se está viendo. Los datos salen siempre de la base local.
 */
@Composable
fun App() {
    TemaMilkFlow {
        val sesion = viewModel { Contenedor.sesionViewModel() }
        val estado by sesion.uiState.collectAsState()

        var listo by remember { mutableStateOf(false) }

        LaunchedEffect(Unit) {
            sesion.iniciar()
            listo = true
        }

        Box(Modifier.fillMaxSize().background(MaterialTheme.colorScheme.background)) {
            when {
                !listo -> Box(Modifier.fillMaxSize(), Alignment.Center) { CircularProgressIndicator() }
                estado.sesion == null -> PantallaLogin(sesion)
                else -> Caparazon(sesion)
            }
        }
    }
}

/** Menú lateral por rol + barra superior con el estado de la sincronización. */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun Caparazon(sesion: SesionViewModel) {
    val sincronizacion = viewModel { Contenedor.sincronizacionViewModel() }
    val estado by sesion.uiState.collectAsState()
    val estadoSync by sincronizacion.estadoSync.collectAsState()

    val usuario = estado.sesion?.usuario ?: return
    val rol = Rol.desde(usuario.role)

    val navegador = remember(rol) { Navegador(rol) }
    val cajon = rememberDrawerState(DrawerValue.Closed)
    val alcance = rememberCoroutineScope()

    ModalNavigationDrawer(
        drawerState = cajon,
        drawerContent = {
            ModalDrawerSheet {
                Column(Modifier.padding(20.dp).verticalScroll(rememberScrollState())) {
                    MarcaHuata()
                    SelectorTema()

                    Spacer(Modifier.height(16.dp))
                    Text(usuario.name, style = MaterialTheme.typography.titleMedium)
                    Text(
                        rol.etiqueta,
                        style = MaterialTheme.typography.bodySmall,
                        color = coloresMilkFlow.textoSuave,
                    )

                    Spacer(Modifier.height(16.dp))
                    HorizontalDivider(color = coloresMilkFlow.borde)
                    Spacer(Modifier.height(8.dp))

                    rol.menu.forEach { pantalla ->
                        val activa = navegador.actual == pantalla
                        val pendientes = if (pantalla == Pantalla.SINCRONIZACION) estado.cola.size else 0

                        Row(
                            Modifier.fillMaxWidth()
                                .background(
                                    if (activa) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.surface,
                                    RoundedCornerShape(14.dp),
                                )
                                .clickable {
                                    navegador.irDesdeMenu(pantalla)
                                    alcance.launch { cajon.close() }
                                }
                                .padding(horizontal = 14.dp, vertical = 12.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(10.dp),
                        ) {
                            Text(pantalla.icono)
                            Text(
                                pantalla.titulo,
                                modifier = Modifier.weight(1f),
                                style = MaterialTheme.typography.bodyMedium,
                                color = if (activa) MaterialTheme.colorScheme.onPrimary
                                else MaterialTheme.colorScheme.onSurface,
                            )
                            if (pendientes > 0) {
                                Text(
                                    "$pendientes",
                                    style = MaterialTheme.typography.labelSmall,
                                    fontWeight = FontWeight.Black,
                                    color = coloresMilkFlow.aviso,
                                )
                            }
                        }
                        Spacer(Modifier.height(4.dp))
                    }

                    Spacer(Modifier.height(12.dp))
                    HorizontalDivider(color = coloresMilkFlow.borde)

                    TextButton(onClick = {
                        alcance.launch {
                            cajon.close()
                            sesion.cerrarSesion()
                        }
                    }) {
                        Text("Cerrar sesión", color = coloresMilkFlow.peligro)
                    }
                }
            }
        },
    ) {
        Scaffold(
            topBar = {
                TopAppBar(
                    title = {
                        Column {
                            Text(navegador.actual.titulo, style = MaterialTheme.typography.titleMedium)
                            Text(
                                Fechas.nombreDia(Fechas.hoy()) + " " + Fechas.corta(Fechas.hoy()),
                                style = MaterialTheme.typography.labelSmall,
                                color = coloresMilkFlow.textoSuave,
                            )
                        }
                    },
                    navigationIcon = {
                        TextButton(onClick = {
                            if (navegador.puedeVolver) navegador.volver()
                            else alcance.launch { cajon.open() }
                        }) {
                            Text(if (navegador.puedeVolver) "‹" else "☰", style = MaterialTheme.typography.titleLarge)
                        }
                    },
                    actions = { IndicadorSync(sincronizacion) },
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = MaterialTheme.colorScheme.surface,
                    ),
                )
            },
        ) { relleno ->
            Box(Modifier.fillMaxSize().padding(relleno)) {
                Contenido(navegador)
            }
        }
    }

    // Aviso permanente cuando hay trabajo esperando señal.
    LaunchedEffect(estadoSync.pendientes) { /* el indicador se recompone solo */ }
}

@Composable
private fun IndicadorSync(sincronizacion: SincronizacionViewModel) {
    val estado by sincronizacion.uiState.collectAsState()
    val sync by sincronizacion.estadoSync.collectAsState()

    val pendientes = estado.cola.size
    val color = when {
        sync.sincronizando -> coloresMilkFlow.info
        pendientes > 0 -> coloresMilkFlow.aviso
        estado.ultimoErrorSync != null -> coloresMilkFlow.peligro
        else -> coloresMilkFlow.exito
    }

    val texto = when {
        sync.sincronizando -> "Sincronizando"
        pendientes > 0 -> "$pendientes sin subir"
        estado.ultimoErrorSync != null -> "Sin conexión"
        else -> "Al día"
    }

    Row(
        Modifier.padding(end = 12.dp)
            .background(color.copy(alpha = 0.15f), RoundedCornerShape(999.dp))
            .clickable { sincronizacion.sincronizarEnSegundoPlano() }
            .padding(horizontal = 12.dp, vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (sync.sincronizando) {
            CircularProgressIndicator(Modifier.width(12.dp).height(12.dp), strokeWidth = 2.dp, color = color)
        } else {
            Box(Modifier.width(8.dp).height(8.dp).background(color, RoundedCornerShape(999.dp)))
        }
        Text(texto, style = MaterialTheme.typography.labelSmall, color = color, fontWeight = FontWeight.Bold)
    }
}

/**
 * Muestra la pantalla actual. Cada pantalla recibe su ViewModel; los
 * ViewModel se crean una sola vez y sobreviven a la rotación del teléfono.
 */
@Composable
private fun Contenido(navegador: Navegador) {
    when (navegador.actual) {
        Pantalla.PANEL -> PantallaPanel(viewModel { Contenedor.panelViewModel() }, navegador)

        Pantalla.ACOPIO -> PantallaAcopio(viewModel { Contenedor.acopioViewModel() })
        Pantalla.ACOPIO_HISTORIAL -> PantallaHistorialRutas(viewModel { Contenedor.acopioViewModel() })

        Pantalla.CAUDALIMETRO -> PantallaCaudalimetro(viewModel { Contenedor.plantaViewModel() })

        Pantalla.VENTAS -> PantallaVentas(viewModel { Contenedor.ventasViewModel() }, navegador)
        Pantalla.NUEVA_VENTA -> PantallaNuevaVenta(viewModel { Contenedor.ventasViewModel() }, navegador)
        Pantalla.RECIBOS -> PantallaRecibos(viewModel { Contenedor.ventasViewModel() }, navegador)
        Pantalla.RECIBO_DETALLE -> PantallaRecibo(viewModel { Contenedor.ventasViewModel() }, navegador.argumento)

        Pantalla.CALIDAD -> PantallaCalidad(viewModel { Contenedor.calidadViewModel() })

        Pantalla.ZONAS -> PantallaZonas(viewModel { Contenedor.zonasViewModel() })
        Pantalla.SOLICITUDES_ZONA -> PantallaSolicitudesZona(viewModel { Contenedor.zonasViewModel() })

        Pantalla.AUTORIZAR_PAGOS -> PantallaAutorizarPagos(viewModel { Contenedor.pagosViewModel() })
        Pantalla.SOBRES_RUTA -> PantallaSobresRuta(viewModel { Contenedor.pagosViewModel() })
        Pantalla.SOBRES_HISTORIAL -> PantallaHistorialSobres(viewModel { Contenedor.pagosViewModel() })

        Pantalla.TARIFAS -> PantallaTarifas(viewModel { Contenedor.administracionViewModel() })
        Pantalla.AVISOS -> PantallaAvisos(viewModel { Contenedor.administracionViewModel() })
        Pantalla.FINANZAS -> PantallaFinanzas(viewModel { Contenedor.administracionViewModel() })

        Pantalla.MI_ACOPIO -> PantallaMiAcopio(viewModel { Contenedor.productorViewModel() })
        Pantalla.MI_ZONA -> PantallaMiZona(viewModel { Contenedor.productorViewModel() })
        Pantalla.MIS_DESCUENTOS -> PantallaMisDescuentos(viewModel { Contenedor.productorViewModel() })
        Pantalla.MIS_PAGOS -> PantallaMisPagos(viewModel { Contenedor.productorViewModel() }, navegador)
        Pantalla.MI_CALIDAD -> PantallaMiCalidad(viewModel { Contenedor.productorViewModel() })

        Pantalla.SINCRONIZACION -> PantallaSincronizacion(viewModel { Contenedor.sincronizacionViewModel() })
    }
}
