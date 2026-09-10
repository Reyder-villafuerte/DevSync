package pe.edu.upeu.milkflow.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.HttpClientConfig
import io.ktor.client.plugins.auth.Auth
import io.ktor.client.plugins.auth.providers.BearerTokens
import io.ktor.client.plugins.auth.providers.bearer
import io.ktor.client.plugins.DefaultRequest
import io.ktor.client.plugins.contentnegotiation.ContentNegotiation
import io.ktor.client.plugins.logging.LogLevel
import io.ktor.client.plugins.logging.Logging
import io.ktor.client.request.header
import io.ktor.http.ContentType
import io.ktor.http.contentType
import io.ktor.serialization.kotlinx.json.json
import kotlinx.serialization.json.Json
import pe.edu.upeu.milkflow.core.NivelLogHttp

/** Motor HTTP por plataforma: OkHttp en Android, Darwin en iOS. */
expect fun crearHttpClient(bloque: HttpClientConfig<*>.() -> Unit): HttpClient

val jsonMilkFlow: Json = Json {
    ignoreUnknownKeys = true
    explicitNulls = false
    isLenient = true
}

/**
 * Construye el HttpClient de la app:
 *  - ContentNegotiation con kotlinx.serialization
 *  - Logging (nivel según build)
 *  - Auth Bearer: inyecta el token de Sanctum; [proveedorToken] lo lee del
 *    almacenamiento seguro en cada request (así un re-login lo actualiza sin
 *    recrear el cliente).
 */
fun construirClienteMilkFlow(
    urlBase: String,
    proveedorToken: suspend () -> String?,
    registrar: (String) -> Unit = {},
    nivelLog: NivelLogHttp = NivelLogHttp.BASICO,
): HttpClient = crearHttpClient {
    expectSuccess = false // el mapeo de errores lo hace MapeadorErrores

    install(ContentNegotiation) { json(jsonMilkFlow) }

    install(Logging) {
        level = when (nivelLog) {
            NivelLogHttp.NINGUNO -> LogLevel.NONE
            NivelLogHttp.BASICO -> LogLevel.INFO
            NivelLogHttp.TODO -> LogLevel.ALL
        }
        logger = object : io.ktor.client.plugins.logging.Logger {
            override fun log(message: String) = registrar(message)
        }
    }

    install(Auth) {
        bearer {
            // OJO: Ktor CACHEA el resultado de loadTokens en memoria y solo lo
            // vuelve a pedir vía refreshTokens (ante un 401) o si se limpia el
            // provider a mano. Por eso, al cambiar de usuario (logout+login) hay
            // que llamar a ApiMilkFlow.olvidarCredenciales(); si no, se seguiría
            // enviando el token del usuario anterior.
            loadTokens { proveedorToken()?.let { BearerTokens(it, "") } }
            refreshTokens { proveedorToken()?.let { BearerTokens(it, "") } }
            sendWithoutRequest { true }
        }
    }

    install(DefaultRequest) {
        url(urlBase)
        contentType(ContentType.Application.Json)
        header("Accept", "application/json")
    }
}
