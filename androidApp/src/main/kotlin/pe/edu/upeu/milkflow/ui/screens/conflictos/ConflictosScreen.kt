@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.conflictos

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.androidx.compose.koinViewModel
import pe.edu.upeu.milkflow.ui.components.ContenedorEstado
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow

@Composable
fun ConflictosScreen(
    onVolver: () -> Unit,
    vm: ConflictosViewModel = koinViewModel(),
) {
    val estado by vm.estado.collectAsState()
    val reintentando by vm.reintentandoFlow.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Conflictos de sincronización") },
                navigationIcon = {
                    TextButton(onClick = onVolver, modifier = Modifier.objetivoTactil()) { Text("Volver") }
                },
            )
        },
    ) { pad ->
        Column(Modifier.fillMaxSize().padding(pad).padding(Dimens.EspacioM)) {
            Button(
                onClick = vm::reintentar,
                enabled = !reintentando,
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            ) { Text(if (reintentando) "Sincronizando…" else "Reintentar sincronización") }

            ContenedorEstado(estado, Modifier.padding(top = Dimens.EspacioM)) { lista ->
                LazyColumn(verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                    items(lista, key = { it.id }) { c ->
                        Card(
                            shape = Dimens.FormaTarjeta,
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            modifier = Modifier.fillMaxWidth(),
                        ) {
                            Column(Modifier.padding(Dimens.EspacioM), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                Text("${c.entidad} · ${c.operacion}", fontWeight = FontWeight.SemiBold)
                                Text("Registro: ${c.idRegistro.take(12)}…", style = MaterialTheme.typography.bodyMedium, color = LocalColoresMilkFlow.current.tintaSuave)
                                Text(c.motivo, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodyMedium)
                                Text(c.cuando, style = MaterialTheme.typography.bodyMedium, color = LocalColoresMilkFlow.current.tintaSuave)
                            }
                        }
                    }
                }
            }
        }
    }
}
