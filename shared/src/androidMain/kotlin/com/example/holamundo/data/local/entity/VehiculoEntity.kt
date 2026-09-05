package com.example.holamundo.data.local.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey
import com.example.holamundo.data.local.enums.TipoVehiculo

/**
 * Entidad Room que representa un vehículo de la flota municipal de acopio.
 *
 * Tabla: [vehiculos]
 *
 * La flota opera con camiones y motofurgones asignados a 2–3 sectores rurales diarios.
 * [capacidadLitros] se usa para el contador en vivo (litros acumulados vs. capacidad)
 * durante la ruta de recolección.
 */
@Entity(
    tableName = "vehiculos",
    indices = [Index(value = ["placa"], unique = true)]
)
data class VehiculoEntity(

    @PrimaryKey
    @ColumnInfo(name = "id")
    val id: String,

    @ColumnInfo(name = "placa")
    val placa: String,

    @ColumnInfo(name = "tipo")
    val tipo: TipoVehiculo,

    @ColumnInfo(name = "capacidad_litros")
    val capacidadLitros: Double,

    @ColumnInfo(name = "conductor_responsable")
    val conductorResponsable: String
)
