package pe.edu.upeu.milkflow

import android.content.Context
import java.io.File

/**
 * Guarda el stack trace del último crash en un archivo para poder mostrarlo en
 * pantalla la siguiente vez que se abre la app (útil cuando no se tiene acceso
 * a `adb logcat`). Se instala en Application.onCreate().
 */
object CazadorDeCrashes {

    private const val ARCHIVO = "ultimo_crash.txt"

    fun instalar(context: Context) {
        val previo = Thread.getDefaultUncaughtExceptionHandler()
        Thread.setDefaultUncaughtExceptionHandler { hilo, error ->
            runCatching {
                archivo(context).writeText(
                    buildString {
                        appendLine("MilkFlow — crash")
                        appendLine("hilo: ${hilo.name}")
                        appendLine("versión app: ${context.packageName}")
                        appendLine()
                        append(error.stackTraceToString())
                    },
                )
            }
            previo?.uncaughtException(hilo, error)
        }
    }

    fun leerYBorrar(context: Context): String? {
        val f = archivo(context)
        if (!f.exists()) return null
        return runCatching { f.readText() }.getOrNull().also { runCatching { f.delete() } }
    }

    private fun archivo(context: Context) = File(context.filesDir, ARCHIVO)
}
