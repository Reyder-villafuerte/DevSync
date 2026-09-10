@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.supervisor

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.androidx.compose.koinViewModel
import pe.edu.upeu.milkflow.ui.components.ChipEstado
import pe.edu.upeu.milkflow.ui.components.TonoChip
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.Impresion

@Composable
fun HistorialScreen(
    onVolver: () -> Unit,
    vm: HistorialViewModel = koinViewModel(),
) {
    val s by vm.estado.collectAsState()
    val context = LocalContext.current

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Historial de inspecciones") },
                navigationIcon = {
                    TextButton(onClick = onVolver, modifier = Modifier.objetivoTactil()) { Text("Volver") }
                },
            )
        },
    ) { pad ->
        Column(Modifier.fillMaxSize().padding(pad).padding(Dimens.EspacioM)) {
            OutlinedTextField(
                value = s.busqueda,
                onValueChange = vm::onBusqueda,
                label = { Text("Buscar productor (ignora acentos)") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            )

            if (s.productorSeleccionado == null && s.productoresCoincidentes.isNotEmpty()) {
                Column {
                    s.productoresCoincidentes.forEach { p ->
                        OutlinedButton(
                            onClick = { vm.onProductor(p) },
                            modifier = Modifier.fillMaxWidth().objetivoTactil().padding(vertical = 2.dp),
                        ) { Text("${p.nombre} · ${p.codigoPadron}") }
                    }
                }
            }

            Row(
                Modifier.padding(vertical = Dimens.EspacioS),
                horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
            ) {
                RangoHistorial.entries.forEach { r ->
                    FilterChip(
                        selected = s.rango == r,
                        onClick = { vm.onRango(r) },
                        label = { Text(r.etiqueta) },
                        modifier = Modifier.objetivoTactil(),
                    )
                }
            }

            when {
                s.productorSeleccionado == null ->
                    Text("Busque un productor para ver sus inspecciones.", color = LocalColoresMilkFlow.current.tintaSuave)
                s.inspecciones.isEmpty() ->
                    Text("Sin inspecciones en el rango seleccionado.", color = LocalColoresMilkFlow.current.tintaSuave)
                else -> LazyColumn(verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                    items(s.inspecciones, key = { it.id }) { i ->
                        Card(
                            shape = Dimens.FormaTarjeta,
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            modifier = Modifier.fillMaxWidth(),
                        ) {
                            Column(Modifier.padding(Dimens.EspacioM), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text("${i.fecha} ${i.hora}", fontWeight = FontWeight.SemiBold)
                                    ChipEstado(i.dictamen, if (i.rechazaLote) TonoChip.ALERTA else TonoChip.CONFORME)
                                }
                                Text("Supervisor: ${i.supervisor}", style = MaterialTheme.typography.bodyMedium)
                                Text(i.mediciones, style = MaterialTheme.typography.bodyMedium)
                                TextButton(
                                    onClick = {
                                        val uri = Impresion.exportarPdf(context, "acta_${i.id.take(8)}", "Acta de inspección", i.lineasActa)
                                        Impresion.abrirPdf(context, uri)
                                    },
                                    modifier = Modifier.objetivoTactil(),
                                ) { Text("Ver / descargar acta PDF") }
                            }
                        }
                    }
                }
            }
        }
    }
}
