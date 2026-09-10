package pe.edu.upeu.milkflow.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.plugins.auth.authProviders
import io.ktor.client.plugins.auth.providers.BearerAuthProvider
import io.ktor.client.request.get
import io.ktor.client.request.parameter
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.client.statement.HttpResponse
import io.ktor.http.isSuccess
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.data.remote.dto.DictamenRespuestaDto
import pe.edu.upeu.milkflow.data.remote.dto.InspeccionPeticionDto
import pe.edu.upeu.milkflow.data.remote.dto.LoginPeticionDto
import pe.edu.upeu.milkflow.data.remote.dto.LoginRespuestaDto
import pe.edu.upeu.milkflow.data.remote.dto.PullRespuestaDto
import pe.edu.upeu.milkflow.data.remote.dto.PushPeticionDto
import pe.edu.upeu.milkflow.data.remote.dto.PushRespuestaDto

/**
 * Llamadas HTTP tipadas. Devuelven [Resultado]; nunca lanzan por HTTP ni por
 * fallo de red. El mapeo de estado -> [ErrorApp] está centralizado.
 */
class ApiMilkFlow(private val cliente: HttpClient) {

    suspend fun login(peticion: LoginPeticionDto): Resultado<LoginRespuestaDto> =
        solicitar { cliente.post("api/login") { setBody(peticion) } }.decodificar()

    /**
     * Invalida el token que Ktor tiene cacheado en memoria. Debe llamarse al
     * iniciar o cerrar sesión: si no, tras un cambio de usuario se seguiría
     * enviando el Bearer del usuario anterior (bug "rol_sin_acceso").
     */
    fun olvidarCredenciales() {
        cliente.authProviders.filterIsInstance<BearerAuthProvider>().forEach { it.clearToken() }
    }

    suspend fun logout(): Resultado<Unit> =
        solicitar { cliente.post("api/logout") }.aUnit()

    suspend fun push(peticion: PushPeticionDto): Resultado<PushRespuestaDto> =
        // 200 y 207 son ambos "procesado"; 207 solo indica conflictos parciales.
        solicitar(exitosos = setOf(200, 207)) { cliente.post("api/sync/push") { setBody(peticion) } }.decodificar()

    suspend fun pull(desde: String?, ambito: String): Resultado<PullRespuestaDto> =
        solicitar {
            cliente.get("api/sync/pull") {
                desde?.let { parameter("desde", it) }
                parameter("ambito", ambito)
            }
        }.decodificar()

    suspend fun cerrarJornada(jornadaId: String, cuerpo: Any): Resultado<Unit> =
        solicitar { cliente.post("api/jornadas/$jornadaId/cerrar") { setBody(cuerpo) } }.aUnit()

    suspend fun registrarInspeccion(peticion: InspeccionPeticionDto): Resultado<DictamenRespuestaDto> =
        solicitar { cliente.post("api/inspecciones") { setBody(peticion) } }.decodificar()

    suspend fun marcarAvisoVisto(avisoId: String): Resultado<Unit> =
        solicitar { cliente.post("api/avisos/$avisoId/visto") }.aUnit()

    // ------------------------------------------------------------------
    private suspend fun solicitar(
        exitosos: Set<Int>? = null,
        bloque: suspend () -> HttpResponse,
    ): Resultado<HttpResponse> {
        val respuesta = try {
            bloque()
        } catch (t: Throwable) {
            return Resultado.Fallo(t.aErrorTransporte())
        }
        val ok = if (exitosos != null) respuesta.status.value in exitosos else respuesta.status.isSuccess()
        return if (ok) Resultado.Exito(respuesta) else Resultado.Fallo(respuesta.aErrorApp())
    }

    private suspend inline fun <reified T> Resultado<HttpResponse>.decodificar(): Resultado<T> = when (this) {
        is Resultado.Fallo -> this
        is Resultado.Exito -> try {
            Resultado.Exito(valor.body())
        } catch (t: Throwable) {
            Resultado.Fallo(ErrorApp.ErrorServidor(valor.status.value))
        }
    }

    private fun Resultado<HttpResponse>.aUnit(): Resultado<Unit> = when (this) {
        is Resultado.Fallo -> this
        is Resultado.Exito -> Resultado.Exito(Unit)
    }
}
