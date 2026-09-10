package pe.edu.upeu.milkflow.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.androidx.compose.koinViewModel
import pe.edu.upeu.milkflow.domain.sync.EstadoSincronizacion
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow

/**
 * Indicador de sincronización global (visible en las 3 pantallas):
 *  - "al día"          → punto verde
 *  - "sincronizando"   → spinner
 *  - "N por enviar"    → chip naranja (pendientes en el outbox)
 *  - "N conflictos"    → chip rojo TAPPABLE → pantalla de conflictos
 * y un botón "Sincronizar" que dispara `SincronizarAhoraUseCase`.
 */
@Composable
fun IndicadorSync(
    onAbrirConflictos: () -> Unit,
    modifier: Modifier = Modifier,
    vm: SyncViewModel = koinViewModel(),
) {
    val estado by vm.estado.collectAsState()
    IndicadorSyncContenido(estado, onAbrirConflictos, vm::sincronizar, modifier)
}

@Composable
fun IndicadorSyncContenido(
    estado: EstadoSincronizacion,
    onAbrirConflictos: () -> Unit,
    onSincronizar: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val c = LocalColoresMilkFlow.current
    Row(
        modifier = modifier.padding(horizontal = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        when {
            estado.sincronizando -> {
                CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp)
                Text("Sincronizando…", style = MaterialTheme.typography.bodyMedium)
            }

            estado.conflictos > 0 -> {
                ChipEstado(
                    "${estado.conflictos} conflicto${if (estado.conflictos == 1) "" else "s"}",
                    TonoChip.ALERTA,
                    modifier = Modifier.clickable(onClick = onAbrirConflictos).objetivoTactil(),
                )
                if (estado.pendientes > 0) {
                    ChipEstado("${estado.pendientes} por enviar", TonoChip.ACCION)
                }
            }

            estado.pendientes > 0 -> {
                ChipEstado("${estado.pendientes} por enviar", TonoChip.ACCION)
            }

            else -> {
                Box(
                    Modifier.size(12.dp).background(c.conforme, CircleShape),
                )
                Text("Al día", style = MaterialTheme.typography.bodyMedium, color = c.conforme)
            }
        }

        TextButton(onClick = onSincronizar, modifier = Modifier.objetivoTactil()) {
            Text("Sincronizar")
        }
    }
}
