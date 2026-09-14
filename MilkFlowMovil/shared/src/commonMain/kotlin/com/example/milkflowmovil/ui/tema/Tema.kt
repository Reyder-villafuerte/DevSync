package com.example.milkflowmovil.ui.tema

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
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
val VerdeNoche = Color(0xFF0F1713)
val VerdeNocheSuave = Color(0xFF1B2620)
val Lima = Color(0xFFBEF264)
val LimaOscura = Color(0xFF84CC16)
val Nieve = Color(0xFFF6F8F4)
val Pizarra = Color(0xFF64748B)
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
    ColoresMilkFlow(Verde, Ambar, Rojo, Azul, LimaOscura, Pizarra, Nieve, Color(0xFFE2E8F0))
}

private val EsquemaClaro = lightColorScheme(
    primary = VerdeNoche,
    onPrimary = Color.White,
    primaryContainer = Lima,
    onPrimaryContainer = VerdeNoche,
    secondary = LimaOscura,
    onSecondary = VerdeNoche,
    background = Nieve,
    onBackground = VerdeNoche,
    surface = Color.White,
    onSurface = VerdeNoche,
    surfaceVariant = Color(0xFFEFF2EC),
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
    background = Color(0xFF0B110E),
    onBackground = Color(0xFFE7EDE8),
    surface = VerdeNocheSuave,
    onSurface = Color(0xFFE7EDE8),
    surfaceVariant = Color(0xFF24322B),
    onSurfaceVariant = Color(0xFFA7B5AC),
    error = Color(0xFFF87171),
    outline = Color(0xFF33443A),
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
fun TemaMilkFlow(oscuro: Boolean = isSystemInDarkTheme(), contenido: @Composable () -> Unit) {
    val colores = if (oscuro) {
        ColoresMilkFlow(
            exito = Color(0xFF4ADE80),
            aviso = Color(0xFFFBBF24),
            peligro = Color(0xFFF87171),
            info = Color(0xFF60A5FA),
            acento = Lima,
            textoSuave = Color(0xFFA7B5AC),
            superficieTenue = Color(0xFF141D18),
            borde = Color(0xFF2C3A32),
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
            borde = Color(0xFFE2E8F0),
        )
    }

    CompositionLocalProvider(LocalColoresMilkFlow provides colores) {
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
