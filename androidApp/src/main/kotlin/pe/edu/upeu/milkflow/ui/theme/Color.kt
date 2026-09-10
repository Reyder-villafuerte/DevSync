package pe.edu.upeu.milkflow.ui.theme

import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color

// --- Paleta del sistema de diseño (prototipo validado) ---
val AzulMarino = Color(0xFF1565C0)
val AzulMarinoOscuro = Color(0xFF0D47A1)
val FondoGrisClaro = Color(0xFFF8FAFC)
val Superficie = Color(0xFFFFFFFF)
val VerdeConforme = Color(0xFF2E7D32)
val RojoAlerta = Color(0xFFC62828)
val NaranjaAccion = Color(0xFFE65100)
val TintaSuave = Color(0xFF5B6470)

/**
 * Colores semánticos que NO son roles de `ColorScheme` de Material 3
 * (conforme / alerta / acción principal de pantalla). Se exponen por un
 * CompositionLocal para que cualquier Composable los use sin volver a pasarlos
 * por parámetro.
 */
data class ColoresMilkFlow(
    val conforme: Color = VerdeConforme,
    val conformeSuave: Color = Color(0xFFE6F4EA),
    val alerta: Color = RojoAlerta,
    val alertaSuave: Color = Color(0xFFFDECEA),
    val accion: Color = NaranjaAccion,
    val accionSuave: Color = Color(0xFFFFF3E0),
    val tintaSuave: Color = TintaSuave,
)

val LocalColoresMilkFlow = staticCompositionLocalOf { ColoresMilkFlow() }
