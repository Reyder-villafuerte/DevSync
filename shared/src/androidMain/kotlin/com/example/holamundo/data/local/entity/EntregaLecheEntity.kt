package com.example.holamundo.data.local.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey
import com.example.holamundo.data.local.enums.CalidadLeche
import com.example.holamundo.data.local.enums.ModalidadAcopio
import com.example.holamundo.data.local.enums.SyncStatus
import com.example.holamundo.data.local.enums.TurnoEntrega
import java.util.UUID

/**
 * Entidad Room que registra cada entrega de leche de un productor.
 *
 * Tabla: [entregas_leche]
 *
 * Entidad transaccional central. Cada fila = un pesaje registrado en campo.
 *
 * Offline-First:
 * - [id]: UUID generado en el dispositivo (idempotencia en batch sync).
 * - [syncStatus]: inicia PENDING; WorkManager actualiza a SYNCED/FAILED.
 * - [proveedorNombre]: desnormalizado para listas sin JOIN en offline.
 * - [vehiculoId]: null cuando modalidad es PLANTA.
 * - Litros de entregas RECHAZADO no se suman al acumulado de ruta
 *   (ver [litrosContabilizables]).
 */
@Entity(
    tableName = "entregas_leche",
    foreignKeys = [
        ForeignKey(
            entity = ProveedorEntity::class,
            parentColumns = ["id"],
            childColumns = ["proveedor_id"],
            onDelete = ForeignKey.RESTRICT
        )
    ],
    indices = [
        Index(value = ["proveedor_id"]),
        Index(value = ["sync_status"]),
        Index(value = ["fecha_hora_registro"]),
        Index(value = ["vehiculo_id"]),
        Index(value = ["sector_id", "turno", "fecha_hora_registro"])
    ]
)
data class EntregaLecheEntity(

    @PrimaryKey
    @ColumnInfo(name = "id")
    val id: String = UUID.randomUUID().toString(),

    @ColumnInfo(name = "proveedor_id")
    val proveedorId: String,

    @ColumnInfo(name = "proveedor_nombre")
    val proveedorNombre: String,

    @ColumnInfo(name = "acopiador_id")
    val acopiadorId: String,

    @ColumnInfo(name = "vehiculo_id")
    val vehiculoId: String? = null,

    @ColumnInfo(name = "sector_id")
    val sectorId: String,

    @ColumnInfo(name = "litros")
    val litros: Double,

    @ColumnInfo(name = "turno")
    val turno: TurnoEntrega,

    @ColumnInfo(name = "calidad")
    val calidad: CalidadLeche,

    @ColumnInfo(name = "modalidad")
    val modalidad: ModalidadAcopio,

    @ColumnInfo(name = "observaciones")
    val observaciones: String? = null,

    @ColumnInfo(name = "fecha_hora_registro")
    val fechaHoraRegistro: Long = System.currentTimeMillis(),

    @ColumnInfo(name = "sync_status")
    val syncStatus: SyncStatus = SyncStatus.PENDING,

    @ColumnInfo(name = "last_sync_attempt")
    val lastSyncAttempt: Long? = null,

    @ColumnInfo(name = "created_at")
    val createdAt: Long = System.currentTimeMillis(),

    @ColumnInfo(name = "updated_at")
    val updatedAt: Long = System.currentTimeMillis()
) {
    /** Litros contabilizables (0.0 si calidad = RECHAZADO). Para acumulado en vivo de ruta. */
    val litrosContabilizables: Double
        get() = if (calidad.esAceptable) litros else 0.0

    val pendienteSincronizacion: Boolean
        get() = syncStatus == SyncStatus.PENDING || syncStatus == SyncStatus.FAILED
}
