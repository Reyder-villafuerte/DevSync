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
    primaryContainer = AzulPastel,
    onPrimaryContainer = AzulMarinoOscuro,
    secondary = AzulMarinoOscuro,
    onSecondary = Color.White,
    // Sin esto, los componentes que tiran de `secondaryContainer` (el chip
    // seleccionado, por ejemplo) se pintan con el lila por defecto de M3.
    secondaryContainer = AzulPastel,
    onSecondaryContainer = AzulMarinoOscuro,
    tertiary = AzulMarino,
    onTertiary = Color.White,
    tertiaryContainer = AzulPastel,
    onTertiaryContainer = AzulMarinoOscuro,
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
