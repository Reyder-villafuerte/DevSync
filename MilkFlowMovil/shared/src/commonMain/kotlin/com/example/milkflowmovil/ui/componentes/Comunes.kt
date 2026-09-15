package com.example.milkflowmovil.ui.componentes

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.ui.tema.coloresMilkFlow

/** Tarjeta base: esquinas muy redondeadas y borde tenue, como el panel web. */
@Composable
fun Tarjeta(
    modifier: Modifier = Modifier,
    alPulsar: (() -> Unit)? = null,
    contenido: @Composable () -> Unit,
) {
    Card(
        modifier = if (alPulsar != null) modifier.clickable { alPulsar() } else modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = BorderStroke(1.dp, coloresMilkFlow.borde),
    ) {
        Column(Modifier.padding(16.dp)) { contenido() }
    }
}

/** Cifra grande con su rótulo: litros del día, soles del sobre, moldes en stock. */
@Composable
fun TarjetaMetrica(
    titulo: String,
    valor: String,
    detalle: String? = null,
    color: Color? = null,
    modifier: Modifier = Modifier,
) {
    Tarjeta(modifier) {
        DibujoHuata(titulo)
        Text(
            titulo,
            style = MaterialTheme.typography.labelSmall,
            color = coloresMilkFlow.textoSuave,
        )
        Spacer(Modifier.height(6.dp))
        Text(
            valor,
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Black,
            color = color ?: MaterialTheme.colorScheme.onSurface,
        )
        if (detalle != null) {
            Spacer(Modifier.height(2.dp))
            Text(detalle, style = MaterialTheme.typography.bodySmall, color = coloresMilkFlow.textoSuave)
        }
    }
}

@Composable
fun EncabezadoSeccion(titulo: String, subtitulo: String? = null) {
    Column(Modifier.fillMaxWidth().padding(bottom = 10.dp)) {
        Text(titulo, style = MaterialTheme.typography.titleMedium)
        if (subtitulo != null) {
            Text(subtitulo, style = MaterialTheme.typography.bodySmall, color = coloresMilkFlow.textoSuave)
        }
    }
}

/** Distintivo de estado (conforme, pendiente, sin subir…). */
@Composable
fun Etiqueta(texto: String, color: Color, modifier: Modifier = Modifier) {
    Box(
        modifier
            .background(color.copy(alpha = 0.14f), RoundedCornerShape(999.dp))
            .padding(horizontal = 10.dp, vertical = 4.dp)
    ) {
        Text(texto, style = MaterialTheme.typography.labelSmall, color = color, fontWeight = FontWeight.Bold)
    }
}

@Composable
fun BotonPrimario(
    texto: String,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    cargando: Boolean = false,
    alPulsar: () -> Unit,
) {
    Button(
        onClick = alPulsar,
        modifier = modifier.height(52.dp),
        enabled = habilitado && !cargando,
        shape = RoundedCornerShape(16.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = MaterialTheme.colorScheme.primary,
            contentColor = MaterialTheme.colorScheme.onPrimary,
        ),
    ) {
        if (cargando) {
            CircularProgressIndicator(
                Modifier.height(18.dp).width(18.dp),
                color = MaterialTheme.colorScheme.onPrimary,
                strokeWidth = 2.dp,
            )
            Spacer(Modifier.width(10.dp))
        }
        Text(texto, fontWeight = FontWeight.Bold)
    }
}

@Composable
fun BotonSecundario(
    texto: String,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    alPulsar: () -> Unit,
) {
    OutlinedButton(
        onClick = alPulsar,
        modifier = modifier.height(48.dp),
        enabled = habilitado,
        shape = RoundedCornerShape(16.dp),
        border = BorderStroke(1.dp, coloresMilkFlow.borde),
    ) {
        Text(texto, color = MaterialTheme.colorScheme.onSurface)
    }
}

@Composable
fun Campo(
    valor: String,
    etiqueta: String,
    alCambiar: (String) -> Unit,
    modifier: Modifier = Modifier,
    numerico: Boolean = false,
    decimal: Boolean = false,
    lineas: Int = 1,
    apoyo: String? = null,
    habilitado: Boolean = true,
) {
    OutlinedTextField(
        value = valor,
        onValueChange = { texto ->
            // En el campo se escribe con prisa: se filtra lo que no es número
            // en lugar de dejar que el teclado meta letras en los litros.
            alCambiar(
                when {
                    decimal -> texto.filter { it.isDigit() || it == '.' || it == ',' }.replace(',', '.')
                    numerico -> texto.filter { it.isDigit() }
                    else -> texto
                }
            )
        },
        label = { Text(etiqueta) },
        supportingText = apoyo?.let { { Text(it, style = MaterialTheme.typography.bodySmall) } },
        singleLine = lineas == 1,
        minLines = lineas,
        enabled = habilitado,
        shape = RoundedCornerShape(14.dp),
        keyboardOptions = KeyboardOptions(
            keyboardType = if (numerico || decimal) KeyboardType.Number else KeyboardType.Text,
            imeAction = if (lineas > 1) ImeAction.Default else ImeAction.Next,
        ),
        modifier = modifier.fillMaxWidth(),
    )
}

@Composable
fun MensajeVacio(texto: String, icono: String = "📭") {
    Column(
        Modifier.fillMaxWidth().padding(vertical = 32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Text(icono, style = MaterialTheme.typography.headlineMedium)
        Text(
            texto,
            style = MaterialTheme.typography.bodyMedium,
            color = coloresMilkFlow.textoSuave,
            textAlign = TextAlign.Center,
        )
    }
}

/** Aviso en línea: error de regla, confirmación o advertencia de trabajo sin subir. */
@Composable
fun Nota(texto: String, color: Color, icono: String = "•") {
    Row(
        Modifier.fillMaxWidth()
            .background(color.copy(alpha = 0.10f), RoundedCornerShape(14.dp))
            .padding(12.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Text(icono)
        Text(texto, style = MaterialTheme.typography.bodySmall, color = color)
    }
}

@Composable
fun FilaDato(etiqueta: String, valor: String, resaltado: Boolean = false, color: Color? = null) {
    Row(
        Modifier.fillMaxWidth().padding(vertical = 3.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
    ) {
        Text(etiqueta, style = MaterialTheme.typography.bodySmall, color = coloresMilkFlow.textoSuave)
        Text(
            valor,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = if (resaltado) FontWeight.Black else FontWeight.Medium,
            color = color ?: MaterialTheme.colorScheme.onSurface,
        )
    }
}
