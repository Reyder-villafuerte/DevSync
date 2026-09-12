package pe.edu.upeu.milkflow.ui.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.IntrinsicSize
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.theme.LocalEstiloKpi

/**
 * Contenedor base de todo el sistema: fondo blanco, esquinas de 16dp y un borde
 * de 1dp. Se usa borde en lugar de sombra porque la app se usa a la intemperie,
 * donde una elevación difusa deja de leerse.
 */
@Composable
fun TarjetaMilkFlow(
    modifier: Modifier = Modifier,
    contenido: @Composable () -> Unit,
) {
    Card(
        modifier = modifier,
        shape = Dimens.FormaTarjeta,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = BorderStroke(Dimens.Borde, LocalColoresMilkFlow.current.borde),
    ) { contenido() }
}

/**
 * KPI: cifra grande arriba, rótulo en versalitas debajo y una barra de acento
 * a la izquierda que codifica por color de qué magnitud se habla.
 */
@Composable
fun TarjetaKpi(
    rotulo: String,
    valor: String,
    modifier: Modifier = Modifier,
    acento: Color? = null,
) {
    val colorAcento = acento ?: MaterialTheme.colorScheme.primary
    TarjetaMilkFlow(modifier) {
        Row(Modifier.height(IntrinsicSize.Min)) {
            Box(
                Modifier.width(4.dp).fillMaxHeight().background(colorAcento),
            )
            Column(Modifier.padding(Dimens.EspacioM)) {
                Text(valor, style = LocalEstiloKpi.current, color = colorAcento)
                Rotulo(rotulo)
            }
        }
    }
}

/** Rótulo secundario: pequeño, espaciado y en tinta suave. */
@Composable
fun Rotulo(texto: String, modifier: Modifier = Modifier) {
    Text(
        texto.uppercase(),
        modifier = modifier,
        style = MaterialTheme.typography.bodyMedium.copy(
            fontSize = 13.sp,
            fontWeight = FontWeight.SemiBold,
            letterSpacing = 0.8.sp,
        ),
        color = LocalColoresMilkFlow.current.tintaSuave,
    )
}

enum class TonoChip { CONFORME, ALERTA, ACCION, NEUTRO }

@Composable
private fun coloresDe(tono: TonoChip): Pair<Color, Color> {
    val c = LocalColoresMilkFlow.current
    return when (tono) {
        TonoChip.CONFORME -> c.conformeSuave to c.conforme
        TonoChip.ALERTA -> c.alertaSuave to c.alerta
        TonoChip.ACCION -> c.accionSuave to c.accion
        TonoChip.NEUTRO -> c.superficieTenue to c.tintaSuave
    }
}

@Composable
fun ChipEstado(texto: String, tono: TonoChip, modifier: Modifier = Modifier) {
    val (fondo, tinta) = coloresDe(tono)
    Text(
        text = texto,
        color = tinta,
        style = MaterialTheme.typography.labelLarge.copy(fontWeight = FontWeight.Bold),
        modifier = modifier
            .background(fondo, RoundedCornerShape(999.dp))
            .padding(horizontal = 12.dp, vertical = 6.dp),
    )
}

/**
 * Inicial del socio en un círculo del color de su estado. Da un ancla visual
 * para recorrer la lista de paradas sin leer cada nombre completo.
 */
@Composable
fun AvatarInicial(nombre: String, tono: TonoChip, modifier: Modifier = Modifier) {
    val (fondo, tinta) = coloresDe(tono)
    Box(
        modifier.size(Dimens.Avatar).clip(CircleShape).background(fondo),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            nombre.trim().take(1).uppercase(),
            color = tinta,
            style = MaterialTheme.typography.titleMedium.copy(fontWeight = FontWeight.Bold),
        )
    }
}

/** Barra de avance. Sin dependencias: dos cajas y una fracción entre 0 y 1. */
@Composable
fun BarraProgreso(fraccion: Float, modifier: Modifier = Modifier, color: Color? = null) {
    val c = LocalColoresMilkFlow.current
    val relleno = color ?: c.conforme
    Box(
        modifier
            .fillMaxWidth()
            .height(Dimens.AlturaBarraProgreso)
            .clip(Dimens.FormaPildora)
            .background(c.superficieTenue),
    ) {
        if (fraccion > 0f) {
            Box(
                Modifier
                    .fillMaxWidth(fraccion.coerceIn(0f, 1f))
                    .height(Dimens.AlturaBarraProgreso)
                    .clip(Dimens.FormaPildora)
                    .background(relleno),
            )
        }
    }
}

/**
 * Estado vacío con jerarquía: título, explicación y (opcionalmente) la acción
 * que lo resuelve. Sustituye a las líneas sueltas de texto centrado.
 */
@Composable
fun EstadoVacio(
    titulo: String,
    mensaje: String? = null,
    textoAccion: String? = null,
    accionHabilitada: Boolean = true,
    onAccion: (() -> Unit)? = null,
    modifier: Modifier = Modifier,
) {
    Column(
        modifier = modifier.fillMaxWidth().padding(Dimens.EspacioL),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
    ) {
        Text(
            titulo,
            style = MaterialTheme.typography.titleLarge,
            textAlign = TextAlign.Center,
        )
        if (mensaje != null) {
            Text(
                mensaje,
                style = MaterialTheme.typography.bodyLarge,
                color = LocalColoresMilkFlow.current.tintaSuave,
                textAlign = TextAlign.Center,
            )
        }
        if (textoAccion != null && onAccion != null) {
            BotonPrincipal(textoAccion, onAccion, habilitado = accionHabilitada)
        }
    }
}

/** Botón de acción principal: naranja de acción, alto de objetivo táctil. */
@Composable
fun BotonPrincipal(
    texto: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    anchoCompleto: Boolean = false,
) {
    Button(
        onClick = onClick,
        enabled = habilitado,
        shape = Dimens.FormaPildora,
        colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
        contentPadding = PaddingValues(horizontal = Dimens.EspacioL, vertical = Dimens.EspacioS),
        modifier = (if (anchoCompleto) modifier.fillMaxWidth() else modifier).objetivoTactil(),
    ) {
        Text(texto, style = MaterialTheme.typography.labelLarge)
    }
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
        placeholder = { Text(etiqueta, color = LocalColoresMilkFlow.current.tintaSuave) },
        singleLine = true,
        shape = Dimens.FormaPildora,
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
    TarjetaMilkFlow(modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
        ) {
            Text(titulo, style = MaterialTheme.typography.titleMedium)
            contenido()
        }
    }
}
