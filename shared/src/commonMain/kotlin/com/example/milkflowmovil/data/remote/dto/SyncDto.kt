package com.example.milkflowmovil.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject

/*
 * DTO de los endpoints de sincronización:
 *   POST /api/sync/login   -> PeticionLogin  / RespuestaLogin
 *   POST /api/sync/pull    -> PeticionPull   / RespuestaPull
 *   POST /api/sync/push    -> PeticionPush   / RespuestaPush
 */

@Serializable
data class PeticionLogin(
    val usuario: String,
    val password: String,
    @SerialName("device_id") val dispositivoId: String,
)

@Serializable
data class RespuestaLogin(
    val token: String,
    val usuario: UsuarioDto,
    val avisos: List<AvisoDto> = emptyList(),
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
