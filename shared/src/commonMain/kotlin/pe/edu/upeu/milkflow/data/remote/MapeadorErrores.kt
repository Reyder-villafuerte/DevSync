package pe.edu.upeu.milkflow.data.remote

import io.ktor.client.call.body
import io.ktor.client.statement.HttpResponse
import io.ktor.http.HttpStatusCode
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.data.remote.dto.ErrorReglaDto

/**
 * Traduce una respuesta HTTP no exitosa a un [ErrorApp] tipado. Único lugar del
 * módulo que conoce códigos de estado; el resto del código razona en dominio.
 */
suspend fun HttpResponse.aErrorApp(): ErrorApp = when (status.value) {
    401 -> ErrorApp.NoAutorizado
    403 -> ErrorApp.Prohibido
    in 500..599 -> ErrorApp.ErrorServidor(status.value)
    422 -> mapear422()
    else -> ErrorApp.ErrorServidor(status.value)
}

private suspend fun HttpResponse.mapear422(): ErrorApp {
    val dto = runCatching { body<ErrorReglaDto>() }.getOrNull()
        ?: return ErrorApp.ReglaNegocio("regla_negocio", "Datos rechazados por el servidor.")

    val regla = dto.error ?: "regla_negocio"
    val detalle = dto.mensaje ?: "Datos rechazados por el servidor."

    return when (regla) {
        "stock_insuficiente" -> {
            val ctx = dto.contexto
            ErrorApp.StockInsuficiente(
                idProducto = ctx?.get("productoId")?.jsonPrimitive?.content.orEmpty(),
                disponible = ctx?.get("disponible")?.jsonPrimitive?.content?.toDoubleOrNull() ?: 0.0,
                requerido = ctx?.get("requerido")?.jsonPrimitive?.content?.toDoubleOrNull() ?: 0.0,
            )
        }
        else -> ErrorApp.ReglaNegocio(regla, detalle)
    }
}

/** Excepciones de transporte (sin red, DNS, timeout, TLS) -> ErrorApp.SinRed. */
fun Throwable.aErrorTransporte(): ErrorApp = when (this) {
    is io.ktor.client.plugins.HttpRequestTimeoutException -> ErrorApp.SinRed(this)
    is io.ktor.client.network.sockets.ConnectTimeoutException -> ErrorApp.SinRed(this)
    is io.ktor.client.network.sockets.SocketTimeoutException -> ErrorApp.SinRed(this)
    else -> {
        // IOException y equivalentes de cada plataforma no son un tipo común;
        // se asume que cualquier fallo no-HTTP en la llamada es de red.
        ErrorApp.SinRed(this)
    }
}
