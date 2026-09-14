package com.example.milkflowmovil.datos.remoto

import com.example.milkflowmovil.core.ErrorApp
import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.dominio.Aviso
import com.example.milkflowmovil.dominio.Usuario
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.plugins.HttpTimeout
import io.ktor.client.plugins.contentnegotiation.ContentNegotiation
import io.ktor.client.plugins.defaultRequest
import io.ktor.client.request.get
import io.ktor.client.request.header
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.client.statement.HttpResponse
import io.ktor.http.ContentType
import io.ktor.http.HttpStatusCode
import io.ktor.http.contentType
import io.ktor.serialization.kotlinx.json.json
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonObject

@Serializable
data class PeticionLogin(
    val usuario: String,
    val password: String,
    @SerialName("device_id") val dispositivoId: String,
)

@Serializable
data class RespuestaLogin(
    val token: String,
    val usuario: Usuario,
    val avisos: List<Aviso> = emptyList(),
    @SerialName("servidor_en") val servidorEn: String? = null,
)

@Serializable
data class BloqueEntidad(
    val filas: List<JsonObject> = emptyList(),
    val cursor: String? = null,
    @SerialName("hay_mas") val hayMas: Boolean = false,
)

@Serializable
data class RespuestaPull(
    @SerialName("servidor_en") val servidorEn: String? = null,
    val entidades: Map<String, BloqueEntidad> = emptyMap(),
)

@Serializable
data class PeticionPull(
    val cursores: Map<String, String> = emptyMap(),
    val entidades: List<String> = emptyList(),
)

@Serializable
data class OperacionSubida(
    @SerialName("client_uuid") val clientUuid: String,
    val comando: String,
    val payload: JsonObject,
)

@Serializable
data class PeticionPush(
    @SerialName("device_id") val dispositivoId: String?,
    val operaciones: List<OperacionSubida>,
)

@Serializable
data class ResultadoOperacion(
    @SerialName("client_uuid") val clientUuid: String,
    val comando: String = "",
    val estado: String = "error",
    val mensaje: String? = null,
    val datos: JsonObject? = null,
    val repetida: Boolean = false,
) {
    val aplicada: Boolean get() = estado == "aplicada"
    val rechazada: Boolean get() = estado == "rechazada"
}

@Serializable
data class RespuestaPush(
    val resultados: List<ResultadoOperacion> = emptyList(),
)

/**
 * Cliente HTTP del servidor MilkFlow.
 *
 * Toda llamada devuelve un Resultado: un corte de señal no es una excepción
 * que rompa la app, es un estado normal de trabajo en el campo.
 */
class ApiMilkFlow(private val urlBase: () -> String) {

    private val json = Json {
        ignoreUnknownKeys = true
        isLenient = true
        explicitNulls = false
        encodeDefaults = true
    }

    private val cliente = HttpClient {
        expectSuccess = false

        install(ContentNegotiation) {
            json(json)
        }

        install(HttpTimeout) {
            // Tiempos cortos: en ruta es mejor fallar rápido y reintentar luego
            // que dejar al acopiador mirando una rueda girando.
            connectTimeoutMillis = 10_000
            requestTimeoutMillis = 30_000
            socketTimeoutMillis = 30_000
        }

        defaultRequest {
            header("Accept", "application/json")
        }
    }

    suspend fun login(usuario: String, password: String, dispositivoId: String): Resultado<RespuestaLogin> =
        llamar {
            cliente.post("${base()}/api/sync/login") {
                contentType(ContentType.Application.Json)
                setBody(PeticionLogin(usuario, password, dispositivoId))
            }
        }

    suspend fun pull(token: String, cursores: Map<String, String>): Resultado<RespuestaPull> =
        llamar {
            cliente.post("${base()}/api/sync/pull") {
                header("Authorization", "Bearer $token")
                contentType(ContentType.Application.Json)
                setBody(PeticionPull(cursores = cursores))
            }
        }

    suspend fun push(
        token: String,
        dispositivoId: String?,
        operaciones: List<OperacionSubida>,
    ): Resultado<RespuestaPush> =
        llamar {
            cliente.post("${base()}/api/sync/push") {
                header("Authorization", "Bearer $token")
                contentType(ContentType.Application.Json)
                setBody(PeticionPush(dispositivoId, operaciones))
            }
        }

    suspend fun cerrarSesion(token: String): Resultado<JsonObject> =
        llamar {
            cliente.post("${base()}/api/sync/logout") {
                header("Authorization", "Bearer $token")
            }
        }

    suspend fun ciclosPago(token: String): Resultado<JsonObject> =
        llamar {
            cliente.get("${base()}/api/sync/ciclos-pago") {
                header("Authorization", "Bearer $token")
            }
        }

    private fun base(): String = urlBase().trimEnd('/')

    private suspend inline fun <reified T> llamar(bloque: () -> HttpResponse): Resultado<T> {
        val respuesta = try {
            bloque()
        } catch (e: kotlinx.coroutines.CancellationException) {
            // Una cancelación NO es un problema de red (por ejemplo, la pantalla
            // que lanzó la llamada se cerró). Si se tragara aquí, la app
            // mostraría "sin conexión" teniendo señal perfecta.
            throw e
        } catch (e: Throwable) {
            // Cualquier fallo de transporte sí se traduce a "sin red": la app
            // guarda el trabajo y lo reintenta sola.
            return Resultado.Fallo(ErrorApp.SinRed)
        }

        return when (respuesta.status) {
            HttpStatusCode.OK, HttpStatusCode.Created -> try {
                Resultado.Exito(respuesta.body<T>())
            } catch (e: Throwable) {
                Resultado.Fallo(ErrorApp.Servidor("El servidor respondió en un formato inesperado."))
            }

            HttpStatusCode.Unauthorized -> Resultado.Fallo(ErrorApp.Credenciales)
            HttpStatusCode.Forbidden -> Resultado.Fallo(ErrorApp.CuentaInactiva)
            HttpStatusCode.UnprocessableEntity -> Resultado.Fallo(
                ErrorApp.Regla(mensajeDeError(respuesta) ?: "Datos no válidos.")
            )

            else -> Resultado.Fallo(
                ErrorApp.Servidor(mensajeDeError(respuesta) ?: "Error del servidor (${respuesta.status.value}).")
            )
        }
    }

    private suspend fun mensajeDeError(respuesta: HttpResponse): String? = try {
        val cuerpo = respuesta.body<JsonObject>()
        (cuerpo["message"] ?: cuerpo["mensaje"])?.toString()?.trim('"')
    } catch (e: Throwable) {
        null
    }
}
