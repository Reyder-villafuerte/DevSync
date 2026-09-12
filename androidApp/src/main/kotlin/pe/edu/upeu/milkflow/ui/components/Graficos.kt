package pe.edu.upeu.milkflow.ui.components

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow

/** Una porción de la dona: qué parte del total ocupa y con qué color. */
data class PorcionDona(val etiqueta: String, val fraccion: Float, val color: Color)

/**
 * Dona de composición. Se dibuja con Canvas (arcos) porque el proyecto no
 * incluye ninguna librería de gráficos y no vale la pena añadir una para esto.
 * El hueco central lleva el veredicto dominante, que es lo que el socio busca.
 */
@Composable
fun DonaComposicion(
    porciones: List<PorcionDona>,
    rotuloCentro: String,
    valorCentro: String,
    modifier: Modifier = Modifier,
    diametro: androidx.compose.ui.unit.Dp = 132.dp,
    grosor: androidx.compose.ui.unit.Dp = 22.dp,
) {
    val vacio = LocalColoresMilkFlow.current.superficieTenue
    Box(modifier.size(diametro), contentAlignment = Alignment.Center) {
        Canvas(Modifier.size(diametro)) {
            val trazo = Stroke(width = grosor.toPx(), cap = StrokeCap.Butt)
            val radio = (size.minDimension - grosor.toPx()) / 2f
            val esquina = Offset(
                (size.width - radio * 2) / 2f,
                (size.height - radio * 2) / 2f,
            )
            val medida = Size(radio * 2, radio * 2)

            drawArc(vacio, 0f, 360f, false, esquina, medida, style = trazo)

            // -90° = arranca arriba, como se lee un reloj.
            var inicio = -90f
            porciones.forEach { p ->
                val barrido = p.fraccion.coerceIn(0f, 1f) * 360f
                if (barrido > 0f) {
                    drawArc(p.color, inicio, barrido, false, esquina, medida, style = trazo)
                    inicio += barrido
                }
            }
        }
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(
                rotuloCentro,
                style = MaterialTheme.typography.bodyMedium,
                color = LocalColoresMilkFlow.current.tintaSuave,
            )
            Text(
                valorCentro,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
            )
        }
    }
}

/** Fila de leyenda: punto de color, etiqueta y porcentaje alineado a la derecha. */
@Composable
fun LeyendaDona(porcion: PorcionDona, detalle: String, modifier: Modifier = Modifier) {
    Row(
        modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
    ) {
        Box(Modifier.size(10.dp).clip(CircleShape).background(porcion.color))
        Text(
            porcion.etiqueta,
            style = MaterialTheme.typography.bodyMedium,
            modifier = Modifier.weight(1f),
        )
        Text(
            detalle,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
            color = LocalColoresMilkFlow.current.tintaSuave,
        )
    }
}

/**
 * Círculo pastel con una o dos letras. Hace de icono sin arrastrar la librería
 * de iconos de Material (este proyecto usa Compose Multiplatform y no la trae).
 */
@Composable
fun IconoPastel(
    texto: String,
    fondo: Color,
    tinta: Color,
    modifier: Modifier = Modifier,
    diametro: androidx.compose.ui.unit.Dp = Dimens.Avatar,
) {
    Box(
        modifier.size(diametro).clip(CircleShape).background(fondo),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            texto,
            color = tinta,
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold,
        )
    }
}
