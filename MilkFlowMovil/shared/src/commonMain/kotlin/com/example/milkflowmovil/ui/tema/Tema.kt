package com.example.milkflowmovil.ui.tema

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.*
import com.example.milkflowmovil.datos.local.almacenPlataforma
import androidx.compose.foundation.layout.Row
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp

/**
 * Misma identidad visual que el panel web: verde muy oscuro casi negro con
 * acento lima. El modo oscuro importa de verdad aquí: la ruta arranca a las
 * 4:30 de la mañana.
 */
val VerdeNoche = Color(0xFF295781)
val VerdeNocheSuave = Color(0xFF22313E)
val Lima = Color(0xFF9FBED9)
val LimaOscura = Color(0xFF719681)
val Nieve = Color(0xFFF8F7F3)
val Pizarra = Color(0xFF65747E)
val Rojo = Color(0xFFDC2626)
val Ambar = Color(0xFFF59E0B)
val Verde = Color(0xFF16A34A)
val Azul = Color(0xFF2563EB)

/** Colores de estado que Material3 no cubre. */
data class ColoresMilkFlow(
    val exito: Color,
    val aviso: Color,
    val peligro: Color,
    val info: Color,
    val acento: Color,
    val textoSuave: Color,
    val superficieTenue: Color,
    val borde: Color,
)

val LocalColoresMilkFlow = staticCompositionLocalOf {
    ColoresMilkFlow(Verde, Ambar, Rojo, Azul, LimaOscura, Pizarra, Nieve, Color(0xFFDCE2E4))
}

private val EsquemaClaro = lightColorScheme(
    primary = VerdeNoche,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFE8EEF3),
    onPrimaryContainer = VerdeNoche,
    secondary = LimaOscura,
    onSecondary = VerdeNoche,
    background = Nieve,
    onBackground = Color(0xFF2E3D49),
    surface = Color(0xFFFFFEFA),
    onSurface = Color(0xFF2E3D49),
    surfaceVariant = Color(0xFFE8EEF3),
    onSurfaceVariant = Pizarra,
    error = Rojo,
    outline = Color(0xFFCBD5E1),
)

private val EsquemaOscuro = darkColorScheme(
    primary = Lima,
    onPrimary = VerdeNoche,
    primaryContainer = VerdeNocheSuave,
    onPrimaryContainer = Lima,
    secondary = Lima,
    onSecondary = VerdeNoche,
    background = Color(0xFF19252F),
    onBackground = Color(0xFFE7EDF0),
    surface = VerdeNocheSuave,
    onSurface = Color(0xFFE7EDF0),
    surfaceVariant = Color(0xFF293D4E),
    onSurfaceVariant = Color(0xFFB2C0CA),
    error = Color(0xFFF87171),
    outline = Color(0xFF394A58),
)

private val Tipografia = Typography().let { base ->
    base.copy(
        titleLarge = base.titleLarge.copy(fontWeight = FontWeight.Black, fontSize = 22.sp),
        titleMedium = base.titleMedium.copy(fontWeight = FontWeight.Bold),
        labelLarge = base.labelLarge.copy(fontWeight = FontWeight.Bold),
        // En el campo se lee a contraluz y con guantes: nada por debajo de 13sp.
        bodySmall = base.bodySmall.copy(fontSize = 13.sp),
    )
}

@Composable
fun TemaMilkFlow(contenido: @Composable () -> Unit) {
    val almacen = remember { almacenPlataforma() }
    var modo by remember { mutableStateOf(runCatching { almacen.leer("apariencia.txt") }.getOrNull() ?: "Sistema") }
    val oscuro = modo == "Oscuro" || (modo == "Sistema" && isSystemInDarkTheme())
    val preferencia = PreferenciaTema(modo) { nuevo -> modo = nuevo; runCatching { almacen.escribir("apariencia.txt", nuevo) } }

    val colores = if (oscuro) {
        ColoresMilkFlow(
            exito = Color(0xFF4ADE80),
            aviso = Color(0xFFFBBF24),
            peligro = Color(0xFFF87171),
            info = Color(0xFF60A5FA),
            acento = Lima,
            textoSuave = Color(0xFFB2C0CA),
            superficieTenue = Color(0xFF1E2C38),
            borde = Color(0xFF394A58),
        )
    } else {
        ColoresMilkFlow(
            exito = Verde,
            aviso = Ambar,
            peligro = Rojo,
            info = Azul,
            acento = LimaOscura,
            textoSuave = Pizarra,
            superficieTenue = Nieve,
            borde = Color(0xFFDCE2E4),
        )
    }

    CompositionLocalProvider(LocalColoresMilkFlow provides colores, LocalPreferenciaTema provides preferencia) {
        MaterialTheme(
            colorScheme = if (oscuro) EsquemaOscuro else EsquemaClaro,
            typography = Tipografia,
            content = contenido,
        )
    }
}

/** Atajo para los colores propios dentro de cualquier composable. */
val coloresMilkFlow: ColoresMilkFlow
    @Composable get() = LocalColoresMilkFlow.current

class PreferenciaTema(val modo: String, val cambiar: (String) -> Unit)
val LocalPreferenciaTema = staticCompositionLocalOf { PreferenciaTema("Sistema") {} }
@Composable fun SelectorTema() {
    val preferencia = LocalPreferenciaTema.current
    Row { listOf("Claro", "Oscuro", "Sistema").forEach { modo ->
        TextButton(onClick = { preferencia.cambiar(modo) }) {
            Text(if (preferencia.modo == modo) "✓ $modo" else modo)
        }
    } }
}
