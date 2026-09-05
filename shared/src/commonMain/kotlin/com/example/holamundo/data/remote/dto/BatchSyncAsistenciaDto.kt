package com.example.holamundo.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

// ═══════════════════════════════════════════════════════════════════════════════
// DTOs DE SINCRONIZACIÓN POR LOTES — MÓDULO: EVENTOS Y ASISTENCIA
//
// Flujo: WorkManager detecta red → SyncRepository consulta Room por asistencias
//        PENDING → construye [BatchSyncAsistenciaRequest] →
//        Retrofit POST /api/v1/sync/asistencias → procesa [BatchSyncAsistenciaResponse]
//        → actualiza syncStatus en Room a SYNCED o FAILED.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * DTO plano de un registro de asistencia para envío al servidor.
 * Campos de productor desnormalizados para auditoría sin queries adicionales.
 */
@Serializable
data class AsistenciaEventoDto(
    val id: String,

    @SerialName("evento_id")
    val eventoId: String,

    @SerialName("proveedor_id")
    val proveedorId: String,

    @SerialName("proveedor_dni")
    val proveedorDni: String,

    @SerialName("proveedor_nombre")
    val proveedorNombre: String,

    @SerialName("fecha_hora_marcado")
    val fechaHoraMarcado: Long,

    @SerialName("registrado_por_usuario_id")
    val registradoPorUsuarioId: String,

    @SerialName("created_at")
    val createdAt: Long
)

/**
 * Cuerpo del request para sincronización por lotes de asistencias.
 * Endpoint: POST /api/v1/sync/asistencias
 */
@Serializable
data class BatchSyncAsistenciaRequest(
    @SerialName("dispositivo_id")
    val dispositivoId: String,

    @SerialName("registrador_id")
    val registradorId: String,

    val asistencias: List<AsistenciaEventoDto>
)

/**
 * Respuesta del servidor tras procesar el lote de asistencias.
 *
 * - IDs en [exitososIds] → Room actualiza `syncStatus = SYNCED`
 * - IDs en [fallidosIds] → Room actualiza `syncStatus = FAILED`
 *
 * Conflicto típico: productor ya registrado desde otro dispositivo en el mismo evento.
 */
@Serializable
data class BatchSyncAsistenciaResponse(
    @SerialName("exitosos_ids")
    val exitososIds: List<String>,

    @SerialName("fallidos_ids")
    val fallidosIds: List<String>,

    /** UUID → mensaje de error. Vacío si no hubo fallos. */
    val errores: Map<String, String> = emptyMap(),

    @SerialName("total_asistentes")
    val totalAsistentes: Int = 0,

    @SerialName("timestamp_servidor")
    val timestampServidor: Long
)
