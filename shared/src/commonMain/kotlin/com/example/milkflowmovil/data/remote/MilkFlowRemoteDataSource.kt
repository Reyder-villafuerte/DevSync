package com.example.milkflowmovil.data.remote

import com.example.milkflowmovil.core.ErrorApp
import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.data.remote.dto.OperacionSubida
import com.example.milkflowmovil.data.remote.dto.PeticionLogin
import com.example.milkflowmovil.data.remote.dto.PeticionPull
import com.example.milkflowmovil.data.remote.dto.PeticionPush
import com.example.milkflowmovil.data.remote.dto.RespuestaLogin
import com.example.milkflowmovil.data.remote.dto.RespuestaPull
import com.example.milkflowmovil.data.remote.dto.RespuestaPush
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.bearerAuth
import io.ktor.client.request.get
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.client.statement.HttpResponse
import io.ktor.http.ContentType
import io.ktor.http.HttpStatusCode
import io.ktor.http.contentType
import kotlinx.serialization.json.JsonObject

/**
 * Fuente de datos remota: la única clase que habla con la API REST.
 *
 * Trabaja solo con DTO. Toda llamada devuelve un Resultado: un corte de señal
 * no es una excepción que rompa la app, es un estado normal de trabajo en el
 * campo.
 *
 * Endpoints (relativos a `{urlBase}/api/`, ver HttpClientFactory):
 * | Verbo | Ruta               | Uso                                   |
 * |-------|--------------------|---------------------------------------|
 * | POST  | sync/login         | Inicia sesión con DNI o correo        |
 * | POST  | sync/pull          | Baja los cambios desde un cursor      |
 * | POST  | sync/push          | Sube la cola de operaciones           |
 * | POST  | sync/logout        | Revoca el token                       |
 * | GET   | sync/ciclos-pago   | Consulta los ciclos de pago           |
 */
class MilkFlowRemoteDataSource(private val cliente: HttpClient) {

    suspend fun login(usuario: String, password: String, dispositivoId: String): Resultado<RespuestaLogin> =
        llamar {
            cliente.post("sync/login") {
                contentType(ContentType.Application.Json)
                setBody(PeticionLogin(usuario, password, dispositivoId))
            }
        }

    suspend fun pull(token: String, cursores: Map<String, String>): Resultado<RespuestaPull> =
        llamar {
            cliente.post("sync/pull") {
                bearerAuth(token)
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
            cliente.post("sync/push") {
                bearerAuth(token)
                contentType(ContentType.Application.Json)
                setBody(PeticionPush(dispositivoId, operaciones))
            }
        }

    suspend fun cerrarSesion(token: String): Resultado<JsonObject> =
        llamar {
            cliente.post("sync/logout") {
                bearerAuth(token)
            }
        }

    suspend fun ciclosPago(token: String): Resultado<JsonObject> =
        llamar {
            cliente.get("sync/ciclos-pago") {
                bearerAuth(token)
            }
        }

    /**
     * Ejecuta la petición y traduce el código de estado (diapositiva 6):
     * - 2xx: éxito, se lee el body como DTO.
     * - 401: credenciales; 403: cuenta inactiva; 422: regla de negocio.
     * - resto de 4xx y 5xx: error del servidor, se reintenta más tarde.
     * - sin respuesta (sin señal, timeout): SinRed, el trabajo queda en cola.
     */
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
