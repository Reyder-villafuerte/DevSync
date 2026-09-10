package pe.edu.upeu.milkflow.ui.util

import android.content.Context
import android.provider.Settings

/**
 * Identificador estable del dispositivo para el login (`identificador` en la
 * tabla `dispositivos` del backend). `ANDROID_ID` es constante por instalación
 * y no requiere permisos.
 */
fun idDispositivo(context: Context): String =
    Settings.Secure.getString(context.contentResolver, Settings.Secure.ANDROID_ID)
        ?: "android-desconocido"
