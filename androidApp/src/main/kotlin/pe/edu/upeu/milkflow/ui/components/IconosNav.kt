package pe.edu.upeu.milkflow.ui.components

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.size
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp

/**
 * Iconos de la barra de navegación, dibujados con Canvas.
 *
 * El proyecto usa Compose Multiplatform, que no trae `material-icons`, y no
 * merece la pena arrastrar esa dependencia por cinco siluetas. Cada icono se
 * dibuja sobre una rejilla de 24x24 y se escala al tamaño pedido, así todas
 * comparten grosor y proporción.
 */
enum class IconoNav { INICIO, ENTREGAS, PAGOS, REPORTES, PERFIL }

@Composable
fun IconoNavegacion(icono: IconoNav, color: Color, modifier: Modifier = Modifier, tamano: Dp = 24.dp) {
    Canvas(modifier.size(tamano)) {
        val u = size.minDimension / 24f          // 1 unidad de la rejilla
        fun p(x: Float, y: Float) = Offset(x * u, y * u)
        fun s(w: Float, h: Float) = Size(w * u, h * u)

        when (icono) {
            // Casa: tejado triangular + cuerpo con puerta.
            IconoNav.INICIO -> {
                val tejado = Path().apply {
                    moveTo(12 * u, 2.5f * u)
                    lineTo(22 * u, 11 * u)
                    lineTo(2 * u, 11 * u)
                    close()
                }
                drawPath(tejado, color)
                drawRoundRect(color, p(4.5f, 11f), s(15f, 10.5f), CornerRadius(1.5f * u))
                drawRoundRect(Color.White, p(10f, 15f), s(4f, 6.5f), CornerRadius(0.6f * u))
            }

            // Porrón de leche: cuerpo con hombros y tapa.
            IconoNav.ENTREGAS -> {
                drawRoundRect(color, p(6f, 7.5f), s(12f, 14f), CornerRadius(2.5f * u))
                drawRoundRect(color, p(8.5f, 3f), s(7f, 4f), CornerRadius(1f * u))
                drawRoundRect(Color.White, p(8.5f, 11f), s(7f, 2f), CornerRadius(0.5f * u))
            }

            // Billetera: cuerpo redondeado y broche circular.
            IconoNav.PAGOS -> {
                drawRoundRect(color, p(2.5f, 5.5f), s(19f, 13f), CornerRadius(2.5f * u))
                drawCircle(Color.White, radius = 1.8f * u, center = p(17f, 12f))
            }

            // Barras de reporte, de menor a mayor.
            IconoNav.REPORTES -> {
                drawRoundRect(color, p(3f, 13f), s(4.5f, 8.5f), CornerRadius(1f * u))
                drawRoundRect(color, p(9.75f, 8f), s(4.5f, 13.5f), CornerRadius(1f * u))
                drawRoundRect(color, p(16.5f, 3.5f), s(4.5f, 18f), CornerRadius(1f * u))
            }

            // Persona: cabeza y hombros.
            IconoNav.PERFIL -> {
                drawCircle(color, radius = 4f * u, center = p(12f, 7.5f))
                drawRoundRect(color, p(4f, 14.5f), s(16f, 8f), CornerRadius(4f * u))
            }
        }
    }
}
