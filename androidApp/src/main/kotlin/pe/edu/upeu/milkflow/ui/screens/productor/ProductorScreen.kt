@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.productor

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.domain.model.DictamenCalidad
import pe.edu.upeu.milkflow.domain.model.EstadoRecepcion
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.BotonPrincipal
import pe.edu.upeu.milkflow.ui.components.ChipEstado
import pe.edu.upeu.milkflow.ui.components.DonaComposicion
import pe.edu.upeu.milkflow.ui.components.IconoNav
import pe.edu.upeu.milkflow.ui.components.IconoNavegacion
import pe.edu.upeu.milkflow.ui.components.IconoPastel
import pe.edu.upeu.milkflow.ui.components.IndicadorSync
import pe.edu.upeu.milkflow.ui.components.LeyendaDona
import pe.edu.upeu.milkflow.ui.components.PorcionDona
import pe.edu.upeu.milkflow.ui.components.Rotulo
import pe.edu.upeu.milkflow.ui.components.TarjetaMilkFlow
import pe.edu.upeu.milkflow.ui.components.TonoChip
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.litros
import pe.edu.upeu.milkflow.ui.util.soles

/** Secciones de la app del socio, en el orden de la barra inferior. */
private enum class Seccion(val etiqueta: String, val icono: IconoNav) {
    INICIO("Inicio", IconoNav.INICIO),
    ENTREGAS("Entregas", IconoNav.ENTREGAS),
    PAGOS("Pagos", IconoNav.PAGOS),
    REPORTES("Reportes", IconoNav.REPORTES),
    PERFIL("Perfil", IconoNav.PERFIL),
}

@Composable
fun ProductorScreen(
    sesion: SesionActiva,
    onAbrirConflictos: () -> Unit,
    onCerrarSesion: () -> Unit,
    vm: ProductorViewModel = koinViewModel { parametersOf(sesion) },
) {
    val s by vm.estado.collectAsState()
    var seccion by rememberSaveable { mutableStateOf(Seccion.INICIO) }

    Scaffold(
        bottomBar = { BarraNavegacion(seccion) { seccion = it } },
    ) { pad ->
        Column(Modifier.fillMaxSize().padding(pad)) {
            Cabecera(s, seccion, onAbrirConflictos)
            LazyColumn(
                Modifier.fillMaxSize(),
                contentPadding = PaddingValues(Dimens.EspacioM),
                verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
            ) {
                when (seccion) {
                    Seccion.INICIO -> {
                        item { ResumenCiclo(s) }
                        item { TarjetaPagos(s, compacta = true) { seccion = Seccion.PAGOS } }
                        item { HistorialEntregas(s.entregas.take(5), "Historial de entregas") }
                        item { CalidadDeLeche(s) }
                    }

                    Seccion.ENTREGAS -> item { HistorialEntregas(s.entregas, "Todas mis entregas") }

                    Seccion.PAGOS -> {
                        item { TarjetaPagos(s, compacta = false) }
                        item { ListaLiquidaciones(s) }
                    }

                    Seccion.REPORTES -> {
                        item { SelectorPeriodo(s, vm) }
                        item { GraficoPeriodo(s) }
                        item { ResumenPeriodo(s) }
                    }

                    Seccion.PERFIL -> {
                        item { FichaSocio(s, sesion) }
                        item { RutaAsignada(s, vm) }
                        item { BotonSalir(onCerrarSesion) }
                    }
                }
            }
        }
    }

    s.aviso?.let { aviso ->
        AlertDialog(
            onDismissRequest = { /* obligatorio: no se descarta tocando fuera */ },
            title = { Text(aviso.titulo) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                    s.avisoImagen?.let { img ->
                        Image(
                            bitmap = img,
                            contentDescription = aviso.titulo,
                            contentScale = ContentScale.FillWidth,
                            modifier = Modifier.fillMaxWidth().heightIn(max = 220.dp),
                        )
                    }
                    Text(aviso.contenido, style = MaterialTheme.typography.bodyLarge)
                }
            },
            confirmButton = {
                Button(onClick = vm::confirmarAviso, modifier = Modifier.objetivoTactil()) {
                    Text("Leído y Entendido")
                }
            },
        )
    }

    s.sheet?.let { sheet ->
        val sheetState = rememberModalBottomSheetState()
        ModalBottomSheet(onDismissRequest = vm::cerrarSolicitud, sheetState = sheetState) {
            Column(
                Modifier.fillMaxWidth().padding(Dimens.EspacioL),
                verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
            ) {
                Text("Solicitar cambio de ruta / zona", style = MaterialTheme.typography.titleLarge)
                Text("Elija la zona a la que quiere pasar:", style = MaterialTheme.typography.bodyMedium)
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    s.zonas.filter { it.id != s.zonaActualId }.forEach { zona ->
                        FilterChip(
                            selected = sheet.zonaSeleccionadaId == zona.id,
                            onClick = { vm.onZonaSolicitud(zona.id) },
                            label = { Text(zona.nombre) },
                            shape = Dimens.FormaPildora,
                            modifier = Modifier.objetivoTactil(),
                        )
                    }
                }
                OutlinedTextField(
                    value = sheet.motivo,
                    onValueChange = vm::onMotivo,
                    label = { Text("Motivo") },
                    modifier = Modifier.fillMaxWidth().heightIn(min = 96.dp),
                )
                if (sheet.error != null) {
                    Text(
                        sheet.error,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodyMedium,
                    )
                }
                BotonPrincipal(
                    texto = if (sheet.guardando) "Enviando…" else "Enviar solicitud",
                    onClick = vm::enviarSolicitud,
                    habilitado = !sheet.guardando,
                    anchoCompleto = true,
                )
            }
        }
    }
}

@Composable
private fun BarraNavegacion(actual: Seccion, onSeleccion: (Seccion) -> Unit) {
    val c = LocalColoresMilkFlow.current
    Column {
        HorizontalDivider(color = c.borde)
        NavigationBar(containerColor = MaterialTheme.colorScheme.surface, tonalElevation = 0.dp) {
            Seccion.entries.forEach { s ->
                val seleccionada = s == actual
                NavigationBarItem(
                    selected = seleccionada,
                    onClick = { onSeleccion(s) },
                    icon = {
                        IconoNavegacion(
                            s.icono,
                            if (seleccionada) MaterialTheme.colorScheme.primary else c.tintaSuave,
                        )
                    },
                    label = {
                        Text(
                            s.etiqueta,
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = if (seleccionada) FontWeight.Bold else FontWeight.Normal,
                        )
                    },
                    colors = NavigationBarItemDefaults.colors(
                        selectedTextColor = MaterialTheme.colorScheme.primary,
                        unselectedTextColor = c.tintaSuave,
                        indicatorColor = c.azulPastel,
                    ),
                )
            }
        }
    }
}

/** Cabecera marino: saludo e ID en Inicio, nombre de la sección en el resto. */
@Composable
private fun Cabecera(s: ProductorUiState, seccion: Seccion, onAbrirConflictos: () -> Unit) {
    Column {
        Surface(color = MaterialTheme.colorScheme.primary) {
            Row(
                Modifier.fillMaxWidth().padding(Dimens.EspacioM),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                IconoPastel(
                    texto = s.nombre.trim().take(1).uppercase().ifBlank { "S" },
                    fondo = Color.White.copy(alpha = 0.18f),
                    tinta = Color.White,
                )
                Column(Modifier.weight(1f).padding(start = Dimens.EspacioM)) {
                    Text(
                        if (seccion == Seccion.INICIO) {
                            "Hola, ${s.nombre.ifBlank { "socio" }}"
                        } else {
                            seccion.etiqueta
                        },
                        style = MaterialTheme.typography.titleLarge,
                        color = Color.White,
                    )
                    Text(
                        if (s.codigoPadron.isBlank()) "Proveedor" else "Proveedor ${s.codigoPadron}",
                        style = MaterialTheme.typography.bodyMedium,
                        color = Color.White.copy(alpha = 0.85f),
                    )
                }
            }
        }
        Surface(color = MaterialTheme.colorScheme.surface) {
            IndicadorSync(onAbrirConflictos = onAbrirConflictos, modifier = Modifier.fillMaxWidth())
        }
        HorizontalDivider(color = LocalColoresMilkFlow.current.borde)
    }
}

@Composable
private fun ResumenCiclo(s: ProductorUiState) {
    val c = LocalColoresMilkFlow.current
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "Ciclo de pago",
                    style = MaterialTheme.typography.titleMedium,
                    modifier = Modifier.weight(1f),
                )
                ChipEstado("jue → mié", TonoChip.NEUTRO)
            }
            Row(
                Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                IconoPastel("L", c.azulPastel, MaterialTheme.colorScheme.primary)
                Column(Modifier.weight(1f)) {
                    Rotulo("Litros registrados")
                    Text(
                        litros(s.litrosCiclo),
                        style = MaterialTheme.typography.headlineSmall,
                        color = MaterialTheme.colorScheme.primary,
                    )
                }
                Column(horizontalAlignment = Alignment.End) {
                    Rotulo("Entregas")
                    Text(
                        "${s.entregasCiclo}",
                        style = MaterialTheme.typography.headlineSmall,
                        color = MaterialTheme.colorScheme.primary,
                    )
                }
            }

            HorizontalDivider(color = c.borde)

            DatoEnLinea("Entregado hoy", litros(s.litrosHoy))
            DatoEnLinea("Tarifa vigente", "${soles(s.tarifaLitro)} / L")
            DatoEnLinea("Pago proyectado (viernes)", soles(s.pagoProyectado), destacado = true)
            if (s.litrosFaltantesCiclo > 0.0) {
                ChipEstado(
                    "Planta reporta ${litros(s.litrosFaltantesCiclo)} de menos en el ciclo",
                    TonoChip.ALERTA,
                )
            }
        }
    }
}

/** Tarjeta marino de pagos: el dinero se mira aparte del resto. */
@Composable
private fun TarjetaPagos(s: ProductorUiState, compacta: Boolean, onVerMas: (() -> Unit)? = null) {
    Surface(
        color = MaterialTheme.colorScheme.primary,
        shape = Dimens.FormaTarjeta,
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
        ) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "Reporte de pagos",
                    style = MaterialTheme.typography.titleMedium,
                    color = Color.White,
                    modifier = Modifier.weight(1f),
                )
                if (compacta && onVerMas != null) {
                    TextButton(onClick = onVerMas, modifier = Modifier.objetivoTactil()) {
                        Text("Ver más", color = Color.White)
                    }
                }
            }
            Text(
                "TOTAL PENDIENTE",
                style = MaterialTheme.typography.bodyMedium,
                color = Color.White.copy(alpha = 0.75f),
            )
            Text(
                soles(s.totalPendiente),
                style = MaterialTheme.typography.headlineSmall,
                color = Color.White,
            )
            Row(Modifier.fillMaxWidth().padding(top = Dimens.EspacioS)) {
                Column(Modifier.weight(1f)) {
                    Text(
                        "Último pago",
                        style = MaterialTheme.typography.bodyMedium,
                        color = Color.White.copy(alpha = 0.75f),
                    )
                    Text(
                        s.ultimoPagoEtiqueta ?: "Sin pagos aún",
                        style = MaterialTheme.typography.bodyLarge,
                        color = Color.White,
                    )
                }
                Column {
                    Text(
                        "Monto",
                        style = MaterialTheme.typography.bodyMedium,
                        color = Color.White.copy(alpha = 0.75f),
                    )
                    Text(
                        s.ultimaLiquidacionNeto?.let { soles(it) } ?: "—",
                        style = MaterialTheme.typography.bodyLarge,
                        color = Color.White,
                    )
                }
            }
        }
    }
}

@Composable
private fun ListaLiquidaciones(s: ProductorUiState) {
    val c = LocalColoresMilkFlow.current
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(vertical = Dimens.EspacioM)) {
            Text(
                "Liquidaciones",
                style = MaterialTheme.typography.titleMedium,
                modifier = Modifier.padding(horizontal = Dimens.EspacioM),
            )
            if (s.pagos.isEmpty()) {
                Text(
                    "Todavía no hay liquidaciones emitidas. Se generan al cerrar el ciclo semanal.",
                    style = MaterialTheme.typography.bodyLarge,
                    color = c.tintaSuave,
                    modifier = Modifier.padding(Dimens.EspacioM),
                )
            }
            s.pagos.forEachIndexed { i, pago ->
                if (i > 0) {
                    HorizontalDivider(color = c.borde, modifier = Modifier.padding(horizontal = Dimens.EspacioM))
                }
                Column(
                    Modifier.fillMaxWidth().padding(horizontal = Dimens.EspacioM, vertical = 14.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp),
                ) {
                    Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            "Semana ${pago.etiqueta}",
                            style = MaterialTheme.typography.bodyLarge,
                            modifier = Modifier.weight(1f),
                        )
                        Text(
                            soles(pago.neto),
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(
                            "${litros(pago.litros)} · ${soles(pago.precioLitro)}/L",
                            style = MaterialTheme.typography.bodyMedium,
                            color = c.tintaSuave,
                            modifier = Modifier.weight(1f),
                        )
                        if (pago.tarifaDegradada) ChipEstado("Tarifa degradada", TonoChip.ALERTA)
                        ChipEstado(
                            pago.estado,
                            if (pago.estado == "pagada") TonoChip.CONFORME else TonoChip.NEUTRO,
                        )
                    }
                    if (pago.descuentos > 0.0) {
                        Text(
                            "Descuentos aplicados: ${soles(pago.descuentos)}",
                            style = MaterialTheme.typography.bodyMedium,
                            color = c.alerta,
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun SelectorPeriodo(s: ProductorUiState, vm: ProductorViewModel) {
    Row(horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
        PeriodoProductor.entries.forEach { p ->
            FilterChip(
                selected = s.periodo == p,
                onClick = { vm.onPeriodo(p) },
                label = { Text(p.etiqueta, style = MaterialTheme.typography.bodyMedium) },
                shape = Dimens.FormaPildora,
                colors = FilterChipDefaults.filterChipColors(
                    selectedContainerColor = MaterialTheme.colorScheme.primary,
                    selectedLabelColor = Color.White,
                ),
                modifier = Modifier.objetivoTactil(),
            )
        }
    }
}

/** Barras del periodo elegido. Canvas otra vez: sin librería de gráficos. */
@Composable
private fun GraficoPeriodo(s: ProductorUiState) {
    val c = LocalColoresMilkFlow.current
    val azul = MaterialTheme.colorScheme.primary
    // Las filas llegan de la más reciente a la más antigua; el gráfico se lee
    // al revés, con el tiempo avanzando hacia la derecha.
    val barras = s.filasReporte.take(8).reversed()
    val maximo = barras.maxOfOrNull { it.litros } ?: 0.0

    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
        ) {
            Rotulo("Litros por ${s.periodo.etiqueta.lowercase()}")
            Text(litros(s.litrosPeriodo), style = MaterialTheme.typography.headlineSmall, color = azul)
            if (barras.isEmpty()) {
                Text(
                    "Aún no hay entregas registradas en este corte.",
                    style = MaterialTheme.typography.bodyLarge,
                    color = c.tintaSuave,
                )
                return@Column
            }
            Canvas(Modifier.fillMaxWidth().height(120.dp)) {
                val hueco = 8.dp.toPx()
                // Ancho acotado: con una sola entrega, una barra a todo lo ancho
                // parece un bloque de color, no un dato.
                val ancho = ((size.width - hueco * (barras.size - 1)) / barras.size)
                    .coerceAtMost(44.dp.toPx())
                barras.forEachIndexed { i, fila ->
                    val alto = if (maximo <= 0.0) 0f else (fila.litros / maximo * size.height).toFloat()
                    drawRoundRect(
                        color = azul,
                        topLeft = Offset(i * (ancho + hueco), size.height - alto),
                        size = Size(ancho, alto),
                        cornerRadius = CornerRadius(6.dp.toPx()),
                    )
                }
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(barras.first().etiqueta, style = MaterialTheme.typography.bodyMedium, color = c.tintaSuave)
                if (barras.size > 1) {
                    Text(
                        barras.last().etiqueta,
                        style = MaterialTheme.typography.bodyMedium,
                        color = azul,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
            }
        }
    }
}

@Composable
private fun ResumenPeriodo(s: ProductorUiState) {
    val c = LocalColoresMilkFlow.current
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            Text("Resumen del periodo", style = MaterialTheme.typography.titleMedium)
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioM)) {
                DatoConIcono(
                    "L", "Litros totales", litros(s.litrosPeriodo),
                    c.azulPastel, MaterialTheme.colorScheme.primary, Modifier.weight(1f),
                )
                DatoConIcono(
                    "#", "Entregas", "${s.entregasPeriodo}",
                    c.conformeSuave, c.conforme, Modifier.weight(1f),
                )
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioM)) {
                DatoConIcono(
                    "Ø", "Promedio", litros(s.promedioDiario),
                    c.accionSuave, c.accion, Modifier.weight(1f),
                )
                DatoConIcono(
                    "S/", "Precio por litro", soles(s.tarifaLitro),
                    c.advertenciaSuave, c.advertencia, Modifier.weight(1f),
                )
            }
        }
    }
}

@Composable
private fun DatoConIcono(
    glifo: String,
    rotulo: String,
    valor: String,
    fondo: Color,
    tinta: Color,
    modifier: Modifier = Modifier,
) {
    Row(modifier, verticalAlignment = Alignment.CenterVertically) {
        IconoPastel(glifo, fondo, tinta, diametro = 36.dp)
        Column(Modifier.padding(start = Dimens.EspacioS)) {
            Rotulo(rotulo)
            Text(valor, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
private fun HistorialEntregas(entregas: List<EntregaResumen>, titulo: String) {
    val c = LocalColoresMilkFlow.current
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(vertical = Dimens.EspacioM)) {
            Row(
                Modifier.fillMaxWidth().padding(horizontal = Dimens.EspacioM),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(titulo, style = MaterialTheme.typography.titleMedium, modifier = Modifier.weight(1f))
                if (entregas.isNotEmpty()) Rotulo("${entregas.size}")
            }
            if (entregas.isEmpty()) {
                Text(
                    "Aún no hay entregas registradas.",
                    style = MaterialTheme.typography.bodyLarge,
                    color = c.tintaSuave,
                    modifier = Modifier.padding(Dimens.EspacioM),
                )
            }
            entregas.forEachIndexed { i, e ->
                if (i > 0) {
                    HorizontalDivider(color = c.borde, modifier = Modifier.padding(horizontal = Dimens.EspacioM))
                }
                Row(
                    Modifier.fillMaxWidth().padding(horizontal = Dimens.EspacioM, vertical = 14.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Column(Modifier.weight(1f)) {
                        Text(e.fecha, style = MaterialTheme.typography.bodyLarge)
                        val (texto, tono) = recepcionEnPalabras(e)
                        Text(
                            texto,
                            style = MaterialTheme.typography.bodyMedium,
                            color = when (tono) {
                                TonoChip.ALERTA -> c.alerta
                                TonoChip.CONFORME -> c.conforme
                                TonoChip.ACCION -> c.accion
                                TonoChip.NEUTRO -> c.tintaSuave
                            },
                        )
                    }
                    Text(
                        litros(e.litros),
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                    )
                }
            }
        }
    }
}

private fun recepcionEnPalabras(e: EntregaResumen): Pair<String, TonoChip> = when (e.estadoRecepcion) {
    EstadoRecepcion.CONFORME -> "Conforme en planta" to TonoChip.CONFORME
    EstadoRecepcion.FALTANTE -> "Faltó ${litros(e.litrosFaltantes)} en planta" to TonoChip.ALERTA
    EstadoRecepcion.EXCEDENTE -> "Excedente en planta" to TonoChip.ACCION
    EstadoRecepcion.PENDIENTE -> "Por verificar en planta" to TonoChip.NEUTRO
}

/**
 * Calidad de la leche: reparto real de los dictámenes emitidos sobre este
 * socio. No se usa una escala A/B/C/D porque el reglamento no la tiene; las
 * categorías son las de RN-05 y RN-06.
 */
@Composable
private fun CalidadDeLeche(s: ProductorUiState) {
    val c = LocalColoresMilkFlow.current
    val total = s.calidad.sumOf { it.cantidad }
    val porciones = s.calidad.map { conteo ->
        PorcionDona(
            etiqueta = etiquetaDictamen(conteo.dictamen),
            fraccion = if (total == 0) 0f else conteo.cantidad.toFloat() / total,
            color = when (conteo.dictamen) {
                DictamenCalidad.APROBADO -> c.conforme
                DictamenCalidad.ADVERTENCIA_AGUA -> c.advertencia
                DictamenCalidad.DESCUENTO_RETIRO_AGUA -> c.accion
                DictamenCalidad.RECHAZADO_ACIDEZ, DictamenCalidad.EXPULSION_AGUA -> c.alerta
            },
        )
    }

    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            Text("Calidad de leche", style = MaterialTheme.typography.titleMedium)

            if (total == 0) {
                Text(
                    "Todavía no le han tomado inspecciones de calidad.",
                    style = MaterialTheme.typography.bodyLarge,
                    color = c.tintaSuave,
                )
                return@Column
            }

            Row(
                Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                DonaComposicion(
                    porciones = porciones,
                    rotuloCentro = "Último",
                    valorCentro = if (s.calidadRechaza) "Rechazo" else "Conforme",
                )
                Column(
                    Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
                ) {
                    porciones.forEachIndexed { i, porcion ->
                        val pct = (porcion.fraccion * 100).toInt()
                        LeyendaDona(porcion, "$pct% · ${s.calidad[i].cantidad}")
                    }
                }
            }
            Text(
                "$total inspección(es). Último dictamen: " +
                    etiquetaDictamen(DictamenCalidad.desde(s.estadoCalidad)).lowercase(),
                style = MaterialTheme.typography.bodyMedium,
                color = c.tintaSuave,
            )
        }
    }
}

private fun etiquetaDictamen(d: DictamenCalidad): String = when (d) {
    DictamenCalidad.APROBADO -> "Aprobada"
    DictamenCalidad.ADVERTENCIA_AGUA -> "Advertencia por agua"
    DictamenCalidad.DESCUENTO_RETIRO_AGUA -> "Retiro por reincidencia"
    DictamenCalidad.EXPULSION_AGUA -> "Expulsión por agua"
    DictamenCalidad.RECHAZADO_ACIDEZ -> "Rechazada por acidez"
}

@Composable
private fun FichaSocio(s: ProductorUiState, sesion: SesionActiva) {
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                IconoPastel(
                    s.nombre.trim().take(1).uppercase().ifBlank { "S" },
                    LocalColoresMilkFlow.current.azulPastel,
                    MaterialTheme.colorScheme.primary,
                )
                Column(Modifier.padding(start = Dimens.EspacioM)) {
                    Text(
                        s.nombre.ifBlank { sesion.nombreCompleto },
                        style = MaterialTheme.typography.titleLarge,
                    )
                    Rotulo("Padrón ${s.codigoPadron}")
                }
            }
            HorizontalDivider(color = LocalColoresMilkFlow.current.borde)
            DatoEnLinea("DNI", s.dni.ifBlank { "—" })
            DatoEnLinea("Teléfono", s.telefono ?: "No registrado")
            DatoEnLinea("Zona", s.zonaNombre)
            DatoEnLinea("Ruta", s.rutaAsignada)
            DatoEnLinea("Situación en el padrón", s.estadoPadron.ifBlank { "—" })
            DatoEnLinea("Socio desde", s.fechaIngreso.ifBlank { "—" })
        }
    }
}

@Composable
private fun DatoEnLinea(rotulo: String, valor: String, destacado: Boolean = false) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(
            rotulo,
            style = MaterialTheme.typography.bodyLarge,
            color = LocalColoresMilkFlow.current.tintaSuave,
        )
        Text(
            valor,
            style = if (destacado) MaterialTheme.typography.titleMedium else MaterialTheme.typography.bodyLarge,
            fontWeight = if (destacado) FontWeight.Bold else FontWeight.SemiBold,
            color = if (destacado) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurface,
        )
    }
}

@Composable
private fun RutaAsignada(s: ProductorUiState, vm: ProductorViewModel) {
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            Rotulo("Ruta asignada")
            Text(s.rutaAsignada, style = MaterialTheme.typography.titleLarge)
            s.estadoUltimaSolicitud?.let {
                ChipEstado(
                    "Última solicitud: ${it.clave}",
                    when (it.clave) {
                        "aprobada" -> TonoChip.CONFORME
                        "rechazada" -> TonoChip.ALERTA
                        else -> TonoChip.NEUTRO
                    },
                )
            }
            BotonPrincipal(
                texto = "Solicitar cambio de ruta",
                onClick = vm::abrirSolicitud,
                anchoCompleto = true,
            )
        }
    }
}

@Composable
private fun BotonSalir(onCerrarSesion: () -> Unit) {
    TextButton(
        onClick = onCerrarSesion,
        modifier = Modifier.fillMaxWidth().objetivoTactil(),
    ) {
        Text(
            "Cerrar sesión",
            color = LocalColoresMilkFlow.current.alerta,
            style = MaterialTheme.typography.labelLarge,
        )
    }
}
