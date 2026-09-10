package pe.edu.upeu.milkflow.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.graphics.Color

// Modo claro únicamente (requisito del enunciado).
private val EsquemaClaro = lightColorScheme(
    primary = AzulMarino,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFD6E4FF),
    onPrimaryContainer = AzulMarinoOscuro,
    secondary = AzulMarinoOscuro,
    onSecondary = Color.White,
    background = FondoGrisClaro,
    onBackground = Color(0xFF1F2933),
    surface = Superficie,
    onSurface = Color(0xFF1F2933),
    surfaceVariant = Color(0xFFEEF2F6),
    onSurfaceVariant = TintaSuave,
    error = RojoAlerta,
    onError = Color.White,
    outline = Color(0xFFC3CCD6),
)

@Composable
fun MilkFlowTheme(content: @Composable () -> Unit) {
    CompositionLocalProvider(
        LocalColoresMilkFlow provides ColoresMilkFlow(),
        LocalEstiloKpi provides EstiloKpi,
    ) {
        MaterialTheme(
            colorScheme = EsquemaClaro,
            typography = TipografiaMilkFlow,
            content = content,
        )
    }
}
