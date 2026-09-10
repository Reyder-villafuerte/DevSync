package pe.edu.upeu.milkflow.config

import android.content.Context
import android.content.Intent
import kotlin.system.exitProcess

/**
 * URL del backend, editable en tiempo de ejecución desde la pantalla de login
 * y persistida en `SharedPreferences`. Así se puede apuntar a:
 *  - `http://10.0.2.2:8000/`  (emulador → localhost del host)
 *  - `http://127.0.0.1:8000/` (con `adb reverse tcp:8000 tcp:8000`)
 *  - `http://192.168.x.x:8000/` (teléfono físico en la misma WiFi)
 * sin recompilar. El `HttpClient` de Koin toma este valor al arrancar la app,
 * así que un cambio exige reiniciar la app (la pantalla lo avisa).
 */
object ConfiguracionServidor {

    const val POR_DEFECTO = "http://127.0.0.1:8000/"

    private const val PREFS = "milkflow_config"
    private const val CLAVE_URL = "url_base"

    fun leer(context: Context): String =
        prefs(context).getString(CLAVE_URL, POR_DEFECTO)?.ifBlank { POR_DEFECTO } ?: POR_DEFECTO

    fun guardar(context: Context, url: String) {
        val normalizada = url.trim().let { if (it.endsWith("/")) it else "$it/" }
        prefs(context).edit().putString(CLAVE_URL, normalizada).apply()
    }

    /** Guarda la URL y reinicia el proceso para que Koin reconstruya el HttpClient. */
    fun guardarYReiniciar(context: Context, url: String) {
        guardar(context, url)
        val intent = context.packageManager.getLaunchIntentForPackage(context.packageName)
            ?.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)
        context.startActivity(intent)
        exitProcess(0)
    }

    private fun prefs(context: Context) =
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
}
