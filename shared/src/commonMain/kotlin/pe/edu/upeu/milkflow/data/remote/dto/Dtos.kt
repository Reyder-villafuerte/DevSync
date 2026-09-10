package pe.edu.upeu.milkflow.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject

// ---------- Login ----------

@Serializable
data class DispositivoDto(
    val identificador: String,
    val nombre: String? = null,
    val plataforma: String = "android",
)

@Serializable
data class LoginPeticionDto(
    val dni: String,
    val password: String,
    val dispositivo: DispositivoDto,
)

@Serializable
data class UsuarioDto(
    val id: String,
    val dni: String,
    val nombres: String,
    val apellidos: String,
    val rol: String,
    val rolEtiqueta: String? = null,
)

@Serializable
data class LoginRespuestaDto(
    val token: String,
    val dispositivoId: String,
    val usuario: UsuarioDto,
    val ambito: String,
)

// ---------- /api/sync/push ----------

@Serializable
data class OperacionPushDto(
    val entidad: String,
    val id: String,
    val versionBase: Long = 0,
    val atributos: JsonObject? = null,
    val eliminar: Boolean = false,
)

@Serializable
data class PushPeticionDto(val operaciones: List<OperacionPushDto>)

@Serializable
data class AceptadaDto(
    val id: String,
    val entidad: String,
    val version: Long,
    val resultado: String,
)

@Serializable
data class ConflictoDto(
    val id: String,
    val entidad: String,
    val motivo: String,
    val servidor: JsonObject? = null,
)

@Serializable
data class RechazadaDto(
    val id: String,
    val entidad: String? = null,
    val motivo: String,
)

@Serializable
data class PushRespuestaDto(
    val servidorEn: String? = null,
    val aceptadas: List<AceptadaDto> = emptyList(),
    val conflictos: List<ConflictoDto> = emptyList(),
    val rechazadas: List<RechazadaDto> = emptyList(),
)

// ---------- /api/sync/pull ----------

@Serializable
data class PullRespuestaDto(
    val servidorEn: String,
    val cursor: String,
    val hayMas: Boolean,
    val ambito: String,
    // { "productores": [ {..}, .. ], "preciosCompraLeche": [..], ... }
    val cambios: Map<String, List<JsonObject>> = emptyMap(),
)

// ---------- Errores 422 del backend ----------

@Serializable
data class ErrorReglaDto(
    val error: String? = null,
    val mensaje: String? = null,
    val contexto: JsonObject? = null,
)

// ---------- POST /api/inspecciones ----------

@Serializable
data class InspeccionPeticionDto(
    val id: String,
    val productorId: String,
    val rutaAcopioId: String? = null,
    val registroAcopioId: String? = null,
    val tomadoEn: String,
    val aguaAnadidaPorcentaje: Double? = null,
    val ph: Double? = null,
    val densidad: Double? = null,
    val grasaPorcentaje: Double? = null,
    val solidosNoGrasosPorcentaje: Double? = null,
    val temperatura: Double? = null,
)

@Serializable
data class ControlDto(
    val id: String,
    val dictamen: String,
    val dictamenDetalle: String? = null,
    val rechazaLote: Boolean = false,
    val esReincidencia: Boolean = false,
    val version: Long = 0,
)

@Serializable
data class DictamenRespuestaDto(
    val control: ControlDto,
    val sancion: JsonObject? = null,
    val capacitacion: JsonObject? = null,
)
