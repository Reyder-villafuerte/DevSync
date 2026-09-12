@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.EstadoRecepcion
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.AvatarInicial
import pe.edu.upeu.milkflow.ui.components.BarraProgreso
import pe.edu.upeu.milkflow.ui.components.BotonPrincipal
import pe.edu.upeu.milkflow.ui.components.ChipEstado
import pe.edu.upeu.milkflow.ui.components.EstadoVacio
import pe.edu.upeu.milkflow.ui.components.IndicadorSync
import pe.edu.upeu.milkflow.ui.components.Rotulo
import pe.edu.upeu.milkflow.ui.components.TarjetaKpi
import pe.edu.upeu.milkflow.ui.components.TarjetaMilkFlow
import pe.edu.upeu.milkflow.ui.components.TonoChip
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.litros

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AcopiadorScaffold(
    sesion: SesionActiva,
    onAbrirCierre: () -> Unit,
    onAbrirConflictos: () -> Unit,
    onCerrarSesion: () -> Unit,
    vm: AcopiadorViewModel = koinViewModel { parametersOf(sesion) },
    reportesVm: ReportesViewModel = koinViewModel(),
) {
    val s by vm.estado.collectAsState()
    var pestana by rememberSaveable { mutableIntStateOf(0) }

    Scaffold(
        topBar = {
            Column {
                // Cabecera de marca: de un vistazo, qué ruta y quién la lleva.
                TopAppBar(
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = MaterialTheme.colorScheme.primary,
                        titleContentColor = Color.White,
                        actionIconContentColor = Color.White,
                    ),
                    title = {
                        Column {
                            Text(s.rutaNombre, style = MaterialTheme.typography.titleLarge, color = Color.White)
                            Text(
                                sesion.nombreCompleto,
                                style = MaterialTheme.typography.bodyMedium,
                                color = Color.White.copy(alpha = 0.85f),
                            )
                        }
                    },
                    actions = {
                        TextButton(onClick = onCerrarSesion, modifier = Modifier.objetivoTactil()) {
                            Text("Salir", color = Color.White)
                        }
                    },
                )
                Surface(color = MaterialTheme.colorScheme.surface) {
                    IndicadorSync(onAbrirConflictos = onAbrirConflictos, modifier = Modifier.fillMaxWidth())
                }
                TabRow(
                    selectedTabIndex = pestana,
                    containerColor = MaterialTheme.colorScheme.surface,
                    contentColor = MaterialTheme.colorScheme.primary,
                ) {
                    PestanaAcopiador("Ruta", pestana == 0) { pestana = 0 }
                    PestanaAcopiador("Reportes", pestana == 1) { pestana = 1 }
                }
                HorizontalDivider(color = LocalColoresMilkFlow.current.borde)
            }
        },
        bottomBar = {
            // El cierre de ruta es la acción final del turno: vive fija abajo,
            // no al fondo de una lista de cuarenta paradas.
            if (pestana == 0 && s.hayJornada) {
                Surface(color = MaterialTheme.colorScheme.surface) {
                    Column {
                        HorizontalDivider(color = LocalColoresMilkFlow.current.borde)
                        BotonPrincipal(
                            texto = "Cerrar ruta",
                            onClick = onAbrirCierre,
                            anchoCompleto = true,
                            modifier = Modifier.padding(Dimens.EspacioM),
                        )
                    }
                }
            }
        },
    ) { pad ->
        Column(Modifier.fillMaxSize().padding(pad)) {
            when (pestana) {
                0 -> VistaRuta(s, vm)
                else -> VistaReportes(reportesVm)
            }
        }
    }

    s.sheet?.let { sheet ->
        val sheetState = rememberModalBottomSheetState()
        ModalBottomSheet(onDismissRequest = vm::cerrarSheet, sheetState = sheetState) {
            Column(
                Modifier.fillMaxWidth().padding(Dimens.EspacioL),
                verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
            ) {
                Rotulo("Recolección")
                Text(sheet.nombre, style = MaterialTheme.typography.titleLarge)
                OutlinedTextField(
                    value = sheet.litrosTexto,
                    onValueChange = vm::onLitrosSheet,
                    label = { Text("Litros entregados") },
                    singleLine = true,
                    isError = sheet.error != null,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    modifier = Modifier.fillMaxWidth().objetivoTactil(),
                )
                if (sheet.error != null) {
                    Text(
                        sheet.error,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodyMedium,
                    )
                }
                BotonPrincipal(
                    texto = if (sheet.guardando) "Guardando…" else "Confirmar recolección",
                    onClick = vm::confirmarRecoleccion,
                    habilitado = !sheet.guardando,
                    anchoCompleto = true,
                )
                Spacer(Modifier.height(Dimens.EspacioM))
            }
        }
    }
}

@Composable
private fun PestanaAcopiador(texto: String, seleccionada: Boolean, onClick: () -> Unit) {
    Tab(selected = seleccionada, onClick = onClick, modifier = Modifier.objetivoTactil()) {
        Text(
            texto,
            Modifier.padding(vertical = 14.dp),
            style = MaterialTheme.typography.labelLarge,
            fontWeight = if (seleccionada) FontWeight.Bold else FontWeight.Normal,
            color = if (seleccionada) {
                MaterialTheme.colorScheme.primary
            } else {
                LocalColoresMilkFlow.current.tintaSuave
            },
        )
    }
}

@Composable
private fun VistaRuta(s: AcopiadorUiState, vm: AcopiadorViewModel) {
    if (!s.hayJornada) {
        Column(
            Modifier.fillMaxSize(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            EstadoVacio(
                titulo = "No hay una ruta abierta hoy",
                mensaje = s.avisoJornada
                    ?: "Al iniciar la ruta se cargan las paradas de su padrón y podrá " +
                    "registrar la leche recibida sin conexión.",
                textoAccion = if (s.iniciandoJornada) "Abriendo ruta…" else "Iniciar ruta",
                accionHabilitada = !s.iniciandoJornada && s.puedeIniciarRuta,
                onAccion = vm::iniciarRuta,
            )
        }
        return
    }

    val completas = s.kpiSocios
    val pendientes = (s.totalParadas - completas).coerceAtLeast(0)

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(Dimens.EspacioM),
        verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
    ) {
        item { ResumenRuta(s, completas, pendientes) }

        item {
            OutlinedTextField(
                value = s.busqueda,
                onValueChange = vm::onBusqueda,
                placeholder = { Text("Buscar socio por nombre o código") },
                singleLine = true,
                shape = Dimens.FormaPildora,
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            )
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                FiltroChip("Todos · ${s.totalParadas}", s.filtro == FiltroParada.TODOS) {
                    vm.onFiltro(FiltroParada.TODOS)
                }
                FiltroChip("Pendientes · $pendientes", s.filtro == FiltroParada.PENDIENTE) {
                    vm.onFiltro(FiltroParada.PENDIENTE)
                }
                FiltroChip("Completas · $completas", s.filtro == FiltroParada.COMPLETO) {
                    vm.onFiltro(FiltroParada.COMPLETO)
                }
            }
        }

        if (s.avisoJornada != null) {
            item {
                Text(
                    s.avisoJornada,
                    style = MaterialTheme.typography.bodyMedium,
                    color = LocalColoresMilkFlow.current.accion,
                )
            }
        }

        if (s.paradas.isEmpty()) {
            item {
                EstadoVacio(
                    titulo = "Ninguna parada coincide",
                    mensaje = "Pruebe con otro nombre o quite el filtro.",
                )
            }
        }

        items(s.paradas, key = { it.productorId }) { parada ->
            ParadaFila(parada) { vm.abrirSheet(parada) }
        }
    }
}

/** Estado de la ruta: lo acopiado, el avance y desde qué hora está abierta. */
@Composable
private fun ResumenRuta(s: AcopiadorUiState, completas: Int, pendientes: Int) {
    val c = LocalColoresMilkFlow.current
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Rotulo("Litros acopiados")
                    Text(
                        litros(s.kpiLitros),
                        style = MaterialTheme.typography.headlineSmall,
                        color = MaterialTheme.colorScheme.primary,
                    )
                }
                if (s.abiertaDesde != null) {
                    ChipEstado("Abierta ${s.abiertaDesde}", TonoChip.NEUTRO)
                }
            }

            BarraProgreso(
                fraccion = if (s.totalParadas == 0) 0f else completas.toFloat() / s.totalParadas,
            )
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(
                    "$completas de ${s.totalParadas} socios atendidos",
                    style = MaterialTheme.typography.bodyMedium,
                    color = c.tintaSuave,
                )
                Text(
                    if (pendientes == 0) "Ruta completa" else "$pendientes por visitar",
                    style = MaterialTheme.typography.bodyMedium,
                    color = if (pendientes == 0) c.conforme else c.accion,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun FiltroChip(texto: String, seleccionado: Boolean, onClick: () -> Unit) {
    FilterChip(
        selected = seleccionado,
        onClick = onClick,
        label = { Text(texto, style = MaterialTheme.typography.bodyMedium) },
        shape = Dimens.FormaPildora,
        colors = FilterChipDefaults.filterChipColors(
            selectedContainerColor = MaterialTheme.colorScheme.primaryContainer,
            selectedLabelColor = MaterialTheme.colorScheme.onPrimaryContainer,
        ),
        modifier = Modifier.objetivoTactil(),
    )
}

@Composable
private fun ParadaFila(parada: ParadaUi, onClick: () -> Unit) {
    val c = LocalColoresMilkFlow.current
    val tono = when {
        !parada.completo -> TonoChip.ACCION
        parada.estadoRecepcion == EstadoRecepcion.FALTANTE -> TonoChip.ALERTA
        else -> TonoChip.CONFORME
    }
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Row(
            Modifier
                .fillMaxWidth()
                // Una parada ya registrada no se vuelve a tocar: fondo apagado
                // y sin respuesta al toque.
                .background(if (parada.completo) c.superficieTenue else Color.Transparent)
                .let { if (parada.completo) it else it.clickable(onClick = onClick) }
                .padding(Dimens.EspacioM),
            horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            AvatarInicial(parada.nombre, tono)
            Column(Modifier.weight(1f)) {
                Text(
                    parada.nombre,
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    parada.codigoPadron,
                    style = MaterialTheme.typography.bodyMedium,
                    color = c.tintaSuave,
                )
            }
            when {
                !parada.completo -> ChipEstado("Registrar", TonoChip.ACCION)
                parada.estadoRecepcion == EstadoRecepcion.FALTANTE ->
                    ChipEstado("Faltó ${litros(parada.litrosFaltantes)}", TonoChip.ALERTA)
                else -> ChipEstado("${litros(parada.litros ?: 0.0)} ✓", TonoChip.CONFORME)
            }
        }
    }
}

@Composable
private fun VistaReportes(vm: ReportesViewModel) {
    val s by vm.estado.collectAsState()
    val c = LocalColoresMilkFlow.current
    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(Dimens.EspacioM),
        verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                PeriodoReporte.entries.forEach { p ->
                    FiltroChip(p.etiqueta, s.periodo == p) { vm.onPeriodo(p) }
                }
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                TarjetaKpi("Total litros", litros(s.totalLitros), Modifier.weight(1f))
                TarjetaKpi(
                    "Rutas",
                    "${s.rutas.size}",
                    Modifier.weight(1f),
                    acento = c.conforme,
                )
            }
        }
        if (s.vacio) {
            item {
                EstadoVacio(
                    titulo = "Sin rutas en este periodo",
                    mensaje = "Cada ruta que abra y cierre aparecerá aquí con sus propias entregas.",
                )
            }
        }
        items(s.rutas, key = { it.jornadaId }) { ruta ->
            TarjetaRuta(ruta, s.expandida == ruta.jornadaId) { vm.alternarDetalle(ruta.jornadaId) }
        }
    }
}

/** Una ruta del historial. Se toca para desplegar sus entregas. */
@Composable
private fun TarjetaRuta(ruta: RutaReporte, expandida: Boolean, onAlternar: () -> Unit) {
    val c = LocalColoresMilkFlow.current
    val (etiquetaEstado, tono) = when (ruta.estado) {
        EstadoJornada.EN_CURSO -> "En curso" to TonoChip.ACCION
        EstadoJornada.CERRADA -> "Cerrada" to TonoChip.CONFORME
        EstadoJornada.CONCILIADA -> "Conciliada" to TonoChip.NEUTRO
    }
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(Modifier.fillMaxWidth().clickable(onClick = onAlternar)) {
            Column(
                Modifier.padding(Dimens.EspacioM),
                verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
            ) {
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(
                            ruta.fecha,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.SemiBold,
                        )
                        Text(
                            // El tramo horario es lo que distingue dos rutas del
                            // mismo día.
                            "${ruta.horaInicio} → ${ruta.horaCierre ?: "sin cerrar"}",
                            style = MaterialTheme.typography.bodyMedium,
                            color = c.tintaSuave,
                        )
                    }
                    ChipEstado(etiquetaEstado, tono)
                }
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Bottom) {
                    Column(Modifier.weight(1f)) {
                        Rotulo("Acopiado")
                        Text(
                            litros(ruta.litros),
                            style = MaterialTheme.typography.headlineSmall,
                            color = MaterialTheme.colorScheme.primary,
                        )
                    }
                    Text(
                        if (ruta.socios == 1) "1 entrega" else "${ruta.socios} entregas",
                        style = MaterialTheme.typography.bodyMedium,
                        color = c.tintaSuave,
                    )
                    Text(
                        if (expandida) "  Ocultar" else "  Ver detalle",
                        style = MaterialTheme.typography.labelLarge,
                        color = MaterialTheme.colorScheme.primary,
                    )
                }
            }

            if (expandida) {
                HorizontalDivider(color = c.borde)
                if (ruta.entregas.isEmpty()) {
                    Text(
                        "Esta ruta se cerró sin registrar entregas.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = c.tintaSuave,
                        modifier = Modifier.padding(Dimens.EspacioM),
                    )
                }
                ruta.entregas.forEachIndexed { i, e ->
                    if (i > 0) {
                        HorizontalDivider(
                            color = c.borde,
                            modifier = Modifier.padding(horizontal = Dimens.EspacioM),
                        )
                    }
                    Row(
                        Modifier.fillMaxWidth().padding(horizontal = Dimens.EspacioM, vertical = 12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Column(Modifier.weight(1f)) {
                            Text(e.nombre, style = MaterialTheme.typography.bodyLarge)
                            Text(
                                "${e.codigoPadron} · ${e.hora}",
                                style = MaterialTheme.typography.bodyMedium,
                                color = c.tintaSuave,
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
}
