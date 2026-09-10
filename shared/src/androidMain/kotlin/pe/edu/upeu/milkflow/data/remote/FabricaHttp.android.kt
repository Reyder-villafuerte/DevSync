package pe.edu.upeu.milkflow.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.HttpClientConfig
import io.ktor.client.engine.okhttp.OkHttp

actual fun crearHttpClient(bloque: HttpClientConfig<*>.() -> Unit): HttpClient =
    HttpClient(OkHttp) {
        bloque()
        engine {
            config {
                retryOnConnectionFailure(true)
            }
        }
    }
