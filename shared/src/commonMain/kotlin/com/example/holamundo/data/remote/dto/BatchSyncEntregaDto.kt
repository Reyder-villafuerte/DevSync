package com.example.holamundo.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

// ═══════════════════════════════════════════════════════════════════════════════
// DTOs DE SINCRONIZACIÓN POR LOTES — MÓDULO: ACOPIO DE LECHE
//
// Flujo: WorkManager detecta red → SyncRepository consulta Room por registros
//        PENDING → construye [BatchSyncEntregaRequest] →
//        Retrofit POST /api/v1/sync/entregas → procesa [BatchSyncEntregaResponse]
//        → actualiza syncStatus en Room a SYNCED o FAILED.
//
// Nota KMP: kotlinx.serialization es multiplatform → este archivo compila
// en Android, iOS y JVM sin modificaciones.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * DTO plano de una entrega de leche individual para envío al servidor.
 * Los enums se envían como String (nombre) para compatibilidad con Spring Boot.
 */
@Serializable
data class EntregaLecheDto(
    /** UUID generado en el dispositivo. El servidor lo respeta como PK (idempotencia). */
    val id: String,

    @SerialName("proveedor_id")
    val proveedorId: String,

    @SerialName("proveedor_nombre")
    val proveedorNombre: String,

    @SerialName("acopiador_id")
    val acopiadorId: String,

    /** Null si la entrega fue en planta (sin vehículo asignado). */
    @SerialName("vehiculo_id")
    val vehiculoId: String? = null,

    @SerialName("sector_id")
    val sectorId: String,

    val litros: Double,

    /** Nombre del enum: "MANANA" o "TARDE". */
    val turno: String,

    /** Nombre del enum: "CONFORME", "OBSERVADO" o "RECHAZADO". */
    val calidad: String,

    /** Nombre del enum: "PLANTA" o "RUTA". */
    val modalidad: String,

    val observaciones: String? = null,

    @SerialName("fecha_hora_registro")
    val fechaHoraRegistro: Long,

    @SerialName("created_at")
    val createdAt: Long
)

/**
 * Cuerpo del request para la sincronización por lotes de entregas de leche.
 * Endpoint: POST /api/v1/sync/entregas
 */
@Serializable
data class BatchSyncEntregaRequest(
    @SerialName("dispositivo_id")
    val dispositivoId: String,

    @SerialName("acopiador_id")
    val acopiadorId: String,

    val entregas: List<EntregaLecheDto>
)

/**
 * Respuesta del servidor tras procesar el lote de entregas.
 *
 * - IDs en [exitososIds] → Room actualiza `syncStatus = SYNCED`
 * - IDs en [fallidosIds] → Room actualiza `syncStatus = FAILED`
 */
@Serializable
data class BatchSyncEntregaResponse(
    @SerialName("exitosos_ids")
    val exitososIds: List<String>,

    @SerialName("fallidos_ids")
    val fallidosIds: List<String>,

    /** UUID → mensaje de error. Vacío si no hubo fallos. */
    val errores: Map<String, String> = emptyMap(),

    @SerialName("timestamp_servidor")
    val timestampServidor: Long
)
