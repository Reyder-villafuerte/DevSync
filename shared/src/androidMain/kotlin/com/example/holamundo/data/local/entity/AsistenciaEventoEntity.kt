package com.example.holamundo.data.local.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey
import com.example.holamundo.data.local.enums.SyncStatus
import java.util.UUID

/**
 * Entidad Room que registra la asistencia de un productor a un evento comunal.
 *
 * Tabla: [asistencias_evento]
 *
 * Generada en campo (pase de lista offline mediante escaneo de QR).
 * Índice único (evento_id, proveedor_id) previene duplicados locales.
 * FK CASCADE en evento: si el evento se elimina, las asistencias se borran.
 * FK RESTRICT en proveedor: protege el historial de asistencia.
 */
@Entity(
    tableName = "asistencias_evento",
    foreignKeys = [
        ForeignKey(
            entity = EventoEntity::class,
            parentColumns = ["id"],
            childColumns = ["evento_id"],
            onDelete = ForeignKey.CASCADE
        ),
        ForeignKey(
            entity = ProveedorEntity::class,
            parentColumns = ["id"],
            childColumns = ["proveedor_id"],
            onDelete = ForeignKey.RESTRICT
        )
    ],
    indices = [
        Index(value = ["evento_id", "proveedor_id"], unique = true),
        Index(value = ["proveedor_id"]),
        Index(value = ["sync_status"]),
        Index(value = ["fecha_hora_marcado"])
    ]
)
data class AsistenciaEventoEntity(

    @PrimaryKey
    @ColumnInfo(name = "id")
    val id: String = UUID.randomUUID().toString(),

    @ColumnInfo(name = "evento_id")
    val eventoId: String,

    @ColumnInfo(name = "proveedor_id")
    val proveedorId: String,

    @ColumnInfo(name = "proveedor_dni")
    val proveedorDni: String,

    @ColumnInfo(name = "proveedor_nombre")
    val proveedorNombre: String,

    @ColumnInfo(name = "fecha_hora_marcado")
    val fechaHoraMarcado: Long = System.currentTimeMillis(),

    @ColumnInfo(name = "registrado_por_usuario_id")
    val registradoPorUsuarioId: String,

    @ColumnInfo(name = "sync_status")
    val syncStatus: SyncStatus = SyncStatus.PENDING,

    @ColumnInfo(name = "created_at")
    val createdAt: Long = System.currentTimeMillis(),

    @ColumnInfo(name = "updated_at")
    val updatedAt: Long = System.currentTimeMillis()
) {
    val pendienteSincronizacion: Boolean
        get() = syncStatus == SyncStatus.PENDING || syncStatus == SyncStatus.FAILED
}
