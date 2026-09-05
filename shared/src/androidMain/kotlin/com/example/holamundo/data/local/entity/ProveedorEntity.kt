package com.example.holamundo.data.local.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey
import com.example.holamundo.data.local.enums.ModalidadAcopio
import com.example.holamundo.data.local.enums.SyncStatus

/**
 * Entidad Room que representa a un proveedor/ganadero registrado en el padrón municipal.
 *
 * Tabla: [proveedores]
 *
 * Notas de diseño:
 * - [id]: UUID del servidor — los proveedores son sincronizados desde el backend.
 * - [dni]: índice único para búsqueda rápida en campo (sin scanner QR).
 * - [sectorNombre]: desnormalizado para renderizado offline sin JOIN.
 * - [codigoQr]: contenido del QR (generalmente el [id]) para escaneo con CameraX/ZXing.
 */
@Entity(
    tableName = "proveedores",
    indices = [
        Index(value = ["dni"], unique = true),
        Index(value = ["codigo_padron"]),
        Index(value = ["sector_id"]),
        Index(value = ["sync_status"])
    ]
)
data class ProveedorEntity(

    @PrimaryKey
    @ColumnInfo(name = "id")
    val id: String,

    @ColumnInfo(name = "dni")
    val dni: String,

    @ColumnInfo(name = "codigo_padron")
    val codigoPadron: String,

    @ColumnInfo(name = "nombres")
    val nombres: String,

    @ColumnInfo(name = "apellidos")
    val apellidos: String,

    @ColumnInfo(name = "telefono")
    val telefono: String? = null,

    @ColumnInfo(name = "sector_id")
    val sectorId: String,

    /** Nombre del sector desnormalizado para renderizado offline rápido sin JOIN. */
    @ColumnInfo(name = "sector_nombre")
    val sectorNombre: String,

    @ColumnInfo(name = "modalidad_habitual")
    val modalidadHabitual: ModalidadAcopio,

    @ColumnInfo(name = "codigo_qr")
    val codigoQr: String,

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
) {
    val nombreCompleto: String get() = "$nombres $apellidos"
}
