    package com.example.milkflowmovil

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
import androidx.compose.runtime.setValue
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.di.Contenedor
import com.example.milkflowmovil.dominio.Pantalla
import com.example.milkflowmovil.dominio.Rol
import com.example.milkflowmovil.ui.Navegador
import com.example.milkflowmovil.ui.pantallas.PantallaAutorizarPagos
import com.example.milkflowmovil.ui.pantallas.PantallaAcopio
import com.example.milkflowmovil.ui.pantallas.PantallaAvisos
import com.example.milkflowmovil.ui.pantallas.PantallaCalidad
import com.example.milkflowmovil.ui.pantallas.PantallaCaudalimetro
import com.example.milkflowmovil.ui.pantallas.PantallaFinanzas
import com.example.milkflowmovil.ui.pantallas.PantallaHistorialRutas
import com.example.milkflowmovil.ui.pantallas.PantallaHistorialSobres
import com.example.milkflowmovil.ui.pantallas.PantallaLogin
import com.example.milkflowmovil.ui.pantallas.PantallaMiAcopio
import com.example.milkflowmovil.ui.pantallas.PantallaMiCalidad
import com.example.milkflowmovil.ui.pantallas.PantallaMiZona
import com.example.milkflowmovil.ui.pantallas.PantallaMisDescuentos
import com.example.milkflowmovil.ui.pantallas.PantallaMisPagos
import com.example.milkflowmovil.ui.pantallas.PantallaNuevaVenta
import com.example.milkflowmovil.ui.pantallas.PantallaPanel
import com.example.milkflowmovil.ui.pantallas.PantallaQueseria
import com.example.milkflowmovil.ui.pantallas.PantallaRecibo
import com.example.milkflowmovil.ui.pantallas.PantallaRecibos
import com.example.milkflowmovil.ui.pantallas.PantallaSincronizacion
import com.example.milkflowmovil.ui.pantallas.PantallaSobresRuta
import com.example.milkflowmovil.ui.pantallas.PantallaSolicitudesZona
import com.example.milkflowmovil.ui.pantallas.PantallaTarifas
import com.example.milkflowmovil.ui.pantallas.PantallaVentas
import com.example.milkflowmovil.ui.pantallas.PantallaZonas
import com.example.milkflowmovil.ui.componentes.MarcaHuata
import com.example.milkflowmovil.ui.tema.SelectorTema
import com.example.milkflowmovil.ui.tema.TemaMilkFlow
import com.example.milkflowmovil.ui.tema.coloresMilkFlow
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
        val contenedor = remember { Contenedor() }
        val repositorio = contenedor.repositorio
        val estado by repositorio.estado.collectAsState()

        var listo by remember { mutableStateOf(false) }

        LaunchedEffect(Unit) {
            repositorio.iniciar()
            listo = true
        }

        Box(Modifier.fillMaxSize().background(MaterialTheme.colorScheme.background)) {
            when {
                !listo -> Box(Modifier.fillMaxSize(), Alignment.Center) { CircularProgressIndicator() }
                estado.sesion == null -> PantallaLogin(repositorio)
                else -> Caparazon(repositorio)
            }
        }
    }
}

/** Menú lateral por rol + barra superior con el estado de la sincronización. */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun Caparazon(repositorio: com.example.milkflowmovil.datos.Repositorio) {
    val estado by repositorio.estado.collectAsState()
    val estadoSync by repositorio.estadoSync.collectAsState()

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
                            repositorio.cerrarSesion()
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
                    actions = { IndicadorSync(repositorio) },
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = MaterialTheme.colorScheme.surface,
                    ),
                )
            },
        ) { relleno ->
            Box(Modifier.fillMaxSize().padding(relleno)) {
                Contenido(navegador, repositorio)
            }
        }
    }

    // Aviso permanente cuando hay trabajo esperando señal.
    LaunchedEffect(estadoSync.pendientes) { /* el indicador se recompone solo */ }
}

@Composable
private fun IndicadorSync(repositorio: com.example.milkflowmovil.datos.Repositorio) {
    val estado by repositorio.estado.collectAsState()
    val sync by repositorio.estadoSync.collectAsState()

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
            .clickable { repositorio.sincronizarEnSegundoPlano() }
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

@Composable
private fun Contenido(navegador: Navegador, repositorio: com.example.milkflowmovil.datos.Repositorio) {
    val estado by repositorio.estado.collectAsState()

    when (navegador.actual) {
        Pantalla.PANEL -> PantallaPanel(repositorio, estado, navegador)

        Pantalla.ACOPIO -> PantallaAcopio(repositorio, estado)
        Pantalla.ACOPIO_HISTORIAL -> PantallaHistorialRutas(repositorio, estado)

        Pantalla.CAUDALIMETRO -> PantallaCaudalimetro(repositorio, estado)
        Pantalla.QUESERIA -> PantallaQueseria(repositorio, estado)

        Pantalla.VENTAS -> PantallaVentas(repositorio, estado, navegador)
        Pantalla.NUEVA_VENTA -> PantallaNuevaVenta(repositorio, estado, navegador)
        Pantalla.RECIBOS -> PantallaRecibos(repositorio, estado, navegador)
        Pantalla.RECIBO_DETALLE -> PantallaRecibo(estado, navegador.argumento)

        Pantalla.CALIDAD -> PantallaCalidad(repositorio, estado)

        Pantalla.ZONAS -> PantallaZonas(estado)
        Pantalla.SOLICITUDES_ZONA -> PantallaSolicitudesZona(repositorio, estado)

        Pantalla.AUTORIZAR_PAGOS -> PantallaAutorizarPagos(repositorio, estado)
        Pantalla.SOBRES_RUTA -> PantallaSobresRuta(repositorio, estado)
        Pantalla.SOBRES_HISTORIAL -> PantallaHistorialSobres(estado)

        Pantalla.TARIFAS -> PantallaTarifas(repositorio, estado)
        Pantalla.AVISOS -> PantallaAvisos(repositorio, estado)
        Pantalla.FINANZAS -> PantallaFinanzas(repositorio, estado)

        Pantalla.MI_ACOPIO -> PantallaMiAcopio(estado)
        Pantalla.MI_ZONA -> PantallaMiZona(repositorio, estado)
        Pantalla.MIS_DESCUENTOS -> PantallaMisDescuentos(estado)
        Pantalla.MIS_PAGOS -> PantallaMisPagos(estado, navegador)
        Pantalla.MI_CALIDAD -> PantallaMiCalidad(estado)

        Pantalla.SINCRONIZACION -> PantallaSincronizacion(repositorio, estado)
    }
}
