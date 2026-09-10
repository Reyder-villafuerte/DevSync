@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.foundation.text.KeyboardOptions
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.ChipEstado
import pe.edu.upeu.milkflow.ui.components.IndicadorSync
import pe.edu.upeu.milkflow.ui.components.SeccionTarjeta
import pe.edu.upeu.milkflow.ui.components.TarjetaKpi
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
                TopAppBar(
                    title = {
                        Column {
                            Text("Ruta ${s.rutaNombre}", style = MaterialTheme.typography.titleMedium)
                            Text(
                                "Guardado local ✓ · ${sesion.nombreCompleto}",
                                style = MaterialTheme.typography.bodyMedium,
                                color = LocalColoresMilkFlow.current.tintaSuave,
                            )
                        }
                    },
                    actions = {
                        TextButton(onClick = onCerrarSesion, modifier = Modifier.objetivoTactil()) { Text("Salir") }
                    },
                )
                Row(Modifier.fillMaxWidth().padding(horizontal = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                    IndicadorSync(onAbrirConflictos = onAbrirConflictos)
                }
                TabRow(selectedTabIndex = pestana) {
                    Tab(selected = pestana == 0, onClick = { pestana = 0 }, modifier = Modifier.objetivoTactil()) {
                        Text("Ruta", Modifier.padding(12.dp))
                    }
                    Tab(selected = pestana == 1, onClick = { pestana = 1 }, modifier = Modifier.objetivoTactil()) {
                        Text("Reportes", Modifier.padding(12.dp))
                    }
                }
            }
        },
    ) { pad ->
        Column(Modifier.fillMaxSize().padding(pad)) {
            when (pestana) {
                0 -> VistaRuta(s, vm, onAbrirCierre)
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
                Text("Recolección de ${sheet.nombre}", style = MaterialTheme.typography.titleMedium)
                OutlinedTextField(
                    value = sheet.litrosTexto,
                    onValueChange = vm::onLitrosSheet,
                    label = { Text("Litros entregados") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    modifier = Modifier.fillMaxWidth().objetivoTactil(),
                )
                if (sheet.error != null) {
                    Text(sheet.error, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodyMedium)
                }
                Button(
                    onClick = vm::confirmarRecoleccion,
                    enabled = !sheet.guardando,
                    colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
                    modifier = Modifier.fillMaxWidth().objetivoTactil(),
                ) { Text("Confirmar recolección") }
            }
        }
    }
}

@Composable
private fun VistaRuta(s: AcopiadorUiState, vm: AcopiadorViewModel, onAbrirCierre: () -> Unit) {
    if (!s.hayJornada) {
        Column(
            Modifier.fillMaxSize().padding(Dimens.EspacioL),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(16.dp, Alignment.CenterVertically),
        ) {
            Text("No hay una ruta abierta hoy.", style = MaterialTheme.typography.bodyLarge)
            Button(
                onClick = vm::iniciarRuta,
                enabled = !s.iniciandoJornada,
                colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
                modifier = Modifier.objetivoTactil(),
            ) { Text("Iniciar ruta") }
        }
        return
    }

    LazyColumn(
        Modifier.fillMaxSize().padding(Dimens.EspacioM),
        verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
    ) {
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                TarjetaKpi("Litros acopiados", litros(s.kpiLitros), Modifier.weight(1f))
                TarjetaKpi("Socios atendidos", "${s.kpiSocios}/${s.totalParadas}", Modifier.weight(1f))
            }
        }
        item {
            OutlinedTextField(
                value = s.busqueda,
                onValueChange = vm::onBusqueda,
                label = { Text("Buscar proveedor (ignora acentos)") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth().objetivoTactil().padding(top = Dimens.EspacioS),
            )
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                FiltroChip("Todos", s.filtro == FiltroParada.TODOS) { vm.onFiltro(FiltroParada.TODOS) }
                FiltroChip("Pendiente", s.filtro == FiltroParada.PENDIENTE) { vm.onFiltro(FiltroParada.PENDIENTE) }
                FiltroChip("Completo", s.filtro == FiltroParada.COMPLETO) { vm.onFiltro(FiltroParada.COMPLETO) }
            }
        }
        items(s.paradas, key = { it.productorId }) { parada ->
            ParadaFila(parada) { vm.abrirSheet(parada) }
        }
        item {
            HorizontalDivider(Modifier.padding(vertical = Dimens.EspacioS))
            Button(
                onClick = onAbrirCierre,
                colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            ) { Text("Cerrar ruta") }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun FiltroChip(texto: String, seleccionado: Boolean, onClick: () -> Unit) {
    FilterChip(
        selected = seleccionado,
        onClick = onClick,
        label = { Text(texto) },
        modifier = Modifier.objetivoTactil(),
    )
}

@Composable
private fun ParadaFila(parada: ParadaUi, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        shape = Dimens.FormaTarjeta,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        modifier = Modifier.fillMaxWidth().objetivoTactil(),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(Dimens.EspacioM),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(parada.nombre, style = MaterialTheme.typography.bodyLarge, fontWeight = FontWeight.SemiBold)
                Text(parada.codigoPadron, style = MaterialTheme.typography.bodyMedium, color = LocalColoresMilkFlow.current.tintaSuave)
            }
            when {
                !parada.completo -> ChipEstado("Pendiente", TonoChip.ACCION)
                parada.estadoRecepcion == pe.edu.upeu.milkflow.domain.model.EstadoRecepcion.FALTANTE ->
                    ChipEstado("Faltó ${litros(parada.litrosFaltantes)}", TonoChip.ALERTA)
                parada.estadoRecepcion == pe.edu.upeu.milkflow.domain.model.EstadoRecepcion.CONFORME ->
                    ChipEstado("${litros(parada.litros ?: 0.0)} ✓", TonoChip.CONFORME)
                else -> ChipEstado(litros(parada.litros ?: 0.0), TonoChip.CONFORME)
            }
        }
    }
}

@Composable
private fun VistaReportes(vm: ReportesViewModel) {
    val s by vm.estado.collectAsState()
    LazyColumn(
        Modifier.fillMaxSize().padding(Dimens.EspacioM),
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
                TarjetaKpi("Recolecciones", "${s.totalRecolecciones}", Modifier.weight(1f))
            }
        }
        if (s.vacio) {
            item { Text("Aún no hay recolecciones registradas.", color = LocalColoresMilkFlow.current.tintaSuave) }
        }
        items(s.filas, key = { it.etiqueta }) { fila ->
            SeccionTarjeta(fila.etiqueta) {
                Text("${litros(fila.litros.valor)} · ${fila.recolecciones} recolección(es)")
            }
        }
    }
}
