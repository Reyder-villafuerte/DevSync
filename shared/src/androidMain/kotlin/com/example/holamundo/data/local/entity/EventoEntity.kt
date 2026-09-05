package com.example.holamundo.data.local.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey
import com.example.holamundo.data.local.enums.SyncStatus

/**
 * Entidad Room que representa un evento comunal o institucional.
 *
 * Tabla: [eventos]
 *
 * Incluye asambleas de la asociación ganadera, capacitaciones veterinarias
 * y reuniones municipales. Sincronizados desde el servidor.
 * [sectorDestinoId] null = convocatoria general a todos los sectores.
 */
@Entity(
    tableName = "eventos",
    indices = [
        Index(value = ["fecha_hora_evento"]),
        Index(value = ["sector_destino_id"]),
        Index(value = ["estado_activo"]),
        Index(value = ["sync_status"])
    ]
)
data class EventoEntity(

    @PrimaryKey
    @ColumnInfo(name = "id")
    val id: String,

    @ColumnInfo(name = "titulo")
    val titulo: String,

    @ColumnInfo(name = "descripcion")
    val descripcion: String,

    @ColumnInfo(name = "fecha_hora_evento")
    val fechaHoraEvento: Long,

    @ColumnInfo(name = "lugar")
    val lugar: String,

    @ColumnInfo(name = "sector_destino_id")
    val sectorDestinoId: String? = null,

    @ColumnInfo(name = "estado_activo")
    val estadoActivo: Boolean = true,

    @ColumnInfo(name = "sync_status")
    val syncStatus: SyncStatus = SyncStatus.SYNCED,

    @ColumnInfo(name = "created_at")
    val createdAt: Long = System.currentTimeMillis(),

    @ColumnInfo(name = "updated_at")
    val updatedAt: Long = System.currentTimeMillis(),

    @ColumnInfo(name = "synced_at")
    val syncedAt: Long? = null
)
