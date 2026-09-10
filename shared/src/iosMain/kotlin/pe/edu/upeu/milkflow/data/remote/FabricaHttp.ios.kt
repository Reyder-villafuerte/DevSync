package pe.edu.upeu.milkflow.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.HttpClientConfig
import io.ktor.client.engine.darwin.Darwin

actual fun crearHttpClient(bloque: HttpClientConfig<*>.() -> Unit): HttpClient =
    HttpClient(Darwin) {
        bloque()
        engine {
            configureRequest {
                setAllowsCellularAccess(true)
            }
        }
    }
