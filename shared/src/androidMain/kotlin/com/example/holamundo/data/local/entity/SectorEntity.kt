package com.example.holamundo.data.local.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * Entidad Room que representa un sector geográfico de recolección.
 *
 * Tabla: [sectores]
 *
 * Catálogo sincronizado desde el servidor. Define las zonas rurales de Huata
 * a las que se asignan rutas de acopio y convocatorias de eventos.
 */
@Entity(tableName = "sectores")
data class SectorEntity(

    @PrimaryKey
    @ColumnInfo(name = "id")
    val id: String,

    @ColumnInfo(name = "nombre")
    val nombre: String,

    @ColumnInfo(name = "descripcion")
    val descripcion: String? = null
)
