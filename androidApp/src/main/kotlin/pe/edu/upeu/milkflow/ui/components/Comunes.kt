package pe.edu.upeu.milkflow.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.theme.LocalEstiloKpi

/** Tarjeta de KPI: rótulo + cifra grande (≥28sp). */
@Composable
fun TarjetaKpi(rotulo: String, valor: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        shape = Dimens.FormaTarjeta,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(Modifier.padding(Dimens.EspacioM)) {
            Text(valor, style = LocalEstiloKpi.current, color = MaterialTheme.colorScheme.primary)
            Text(rotulo, style = MaterialTheme.typography.bodyMedium, color = LocalColoresMilkFlow.current.tintaSuave)
        }
    }
}

enum class TonoChip { CONFORME, ALERTA, ACCION, NEUTRO }

@Composable
fun ChipEstado(texto: String, tono: TonoChip, modifier: Modifier = Modifier) {
    val c = LocalColoresMilkFlow.current
    val (fondo, tinta) = when (tono) {
        TonoChip.CONFORME -> c.conformeSuave to c.conforme
        TonoChip.ALERTA -> c.alertaSuave to c.alerta
        TonoChip.ACCION -> c.accionSuave to c.accion
        TonoChip.NEUTRO -> MaterialTheme.colorScheme.surfaceVariant to c.tintaSuave
    }
    Text(
        text = texto,
        color = tinta,
        style = MaterialTheme.typography.labelLarge.copy(fontWeight = FontWeight.Bold),
        modifier = modifier
            .background(fondo, RoundedCornerShape(999.dp))
            .padding(horizontal = 12.dp, vertical = 4.dp),
    )
}

/** Buscador de texto reutilizable (el filtrado sin acentos lo hace el ViewModel). */
@Composable
fun BuscadorTexto(
    valor: String,
    onCambio: (String) -> Unit,
    etiqueta: String,
    modifier: Modifier = Modifier,
) {
    OutlinedTextField(
        value = valor,
        onValueChange = onCambio,
        label = { Text(etiqueta) },
        singleLine = true,
        modifier = modifier.fillMaxWidth().objetivoTactil(),
    )
}

@Composable
fun DialogoConfirmacion(
    titulo: String,
    mensaje: String,
    textoConfirmar: String,
    onConfirmar: () -> Unit,
    onCancelar: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onCancelar,
        title = { Text(titulo) },
        text = { Text(mensaje, style = MaterialTheme.typography.bodyLarge) },
        confirmButton = {
            Button(onClick = onConfirmar, modifier = Modifier.objetivoTactil()) { Text(textoConfirmar) }
        },
        dismissButton = {
            TextButton(onClick = onCancelar, modifier = Modifier.objetivoTactil()) { Text("Cancelar") }
        },
    )
}

@Composable
fun SeccionTarjeta(titulo: String, modifier: Modifier = Modifier, contenido: @Composable () -> Unit) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = Dimens.FormaTarjeta,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
        ) {
            Text(titulo, style = MaterialTheme.typography.titleMedium)
            contenido()
        }
    }
}
