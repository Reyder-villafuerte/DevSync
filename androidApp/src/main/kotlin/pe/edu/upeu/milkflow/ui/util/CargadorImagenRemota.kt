package pe.edu.upeu.milkflow.ui.util

import android.graphics.BitmapFactory
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.asImageBitmap
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.net.HttpURLConnection
import java.net.URL

/**
 * Descarga de imágenes SIN librería (restricción: sólo Compose + Material 3).
 * Se usa para la imagen del aviso obligatorio del productor. Sin caché de disco:
 * la imagen se re-descarga si el Composable se recompone desde cero, lo cual es
 * aceptable porque el aviso se muestra una sola vez por sesión.
 */
object CargadorImagenRemota {

    suspend fun cargar(url: String?): ImageBitmap? {
        if (url.isNullOrBlank()) return null
        return withContext(Dispatchers.IO) {
            runCatching {
                val conexion = (URL(url).openConnection() as HttpURLConnection).apply {
                    connectTimeout = 8_000
                    readTimeout = 8_000
                    instanceFollowRedirects = true
                }
                conexion.inputStream.use { entrada ->
                    BitmapFactory.decodeStream(entrada)?.asImageBitmap()
                }
            }.getOrNull()
        }
    }
}
