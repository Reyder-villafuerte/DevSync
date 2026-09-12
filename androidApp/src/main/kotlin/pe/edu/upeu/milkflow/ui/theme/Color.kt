package pe.edu.upeu.milkflow.ui.theme

import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color

// --- Paleta del sistema de diseño (prototipo validado) ---
// Azul marino de marca: es el color de la cabecera y de las cifras. El azul
// brillante anterior competía con el naranja de acción; el marino lo deja
// respirar y da el contraste que pide el uso a la intemperie.
val AzulMarino = Color(0xFF1B3A6B)
val AzulMarinoOscuro = Color(0xFF122A4E)
/** Relleno pálido para círculos de icono, chips y contenedores de acento. */
val AzulPastel = Color(0xFFE8EEFB)
val FondoGrisClaro = Color(0xFFF5F7FB)
val Superficie = Color(0xFFFFFFFF)
val VerdeConforme = Color(0xFF2E7D32)
val RojoAlerta = Color(0xFFC62828)
val NaranjaAccion = Color(0xFFE65100)
val TintaSuave = Color(0xFF5B6470)
// Bordes y fondos de apoyo. Las tarjetas se separan del fondo con un borde de
// 1dp en vez de sombra: a plena luz del altiplano una sombra no se ve.
val BordeSuave = Color(0xFFE2E8F0)
val SuperficieTenue = Color(0xFFF1F5F9)

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
    val borde: Color = BordeSuave,
    val superficieTenue: Color = SuperficieTenue,
    val azulPastel: Color = AzulPastel,
    /** Ámbar intermedio: advertencia que todavía no es rechazo. */
    val advertencia: Color = Color(0xFFF2B01E),
    val advertenciaSuave: Color = Color(0xFFFDF3D8),
)

val LocalColoresMilkFlow = staticCompositionLocalOf { ColoresMilkFlow() }
