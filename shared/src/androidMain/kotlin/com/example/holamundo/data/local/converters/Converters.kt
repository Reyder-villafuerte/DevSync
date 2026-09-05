package com.example.holamundo.data.local.converters

import androidx.room.TypeConverter
import com.example.holamundo.data.local.enums.CalidadLeche
import com.example.holamundo.data.local.enums.ModalidadAcopio
import com.example.holamundo.data.local.enums.SyncStatus
import com.example.holamundo.data.local.enums.TipoVehiculo
import com.example.holamundo.data.local.enums.TurnoEntrega

/**
 * TypeConverters para Room: convierte los Enums definidos en commonMain
 * a String para almacenamiento en SQLite (Android).
 *
 * Los enums viven en commonMain (Kotlin puro).
 * Este converter vive en androidMain (Room es Android-específico).
 *
 * Estrategia: Enum → String([Enum.name]) para legibilidad en inspección SQLite
 * y compatibilidad directa con el JSON del backend Spring Boot.
 *
 * Registrar en @Database con `@TypeConverters(Converters::class)`.
 */
class Converters {

    // ── SyncStatus ──────────────────────────────────────────────────────────────

    @TypeConverter
    fun syncStatusToString(value: SyncStatus): String = value.name

    @TypeConverter
    fun stringToSyncStatus(value: String): SyncStatus = enumValueOf(value)

    // ── TurnoEntrega ────────────────────────────────────────────────────────────

    @TypeConverter
    fun turnoEntregaToString(value: TurnoEntrega): String = value.name

    @TypeConverter
    fun stringToTurnoEntrega(value: String): TurnoEntrega = enumValueOf(value)

    // ── ModalidadAcopio ─────────────────────────────────────────────────────────

    @TypeConverter
    fun modalidadAcopioToString(value: ModalidadAcopio): String = value.name

    @TypeConverter
    fun stringToModalidadAcopio(value: String): ModalidadAcopio = enumValueOf(value)

    // ── CalidadLeche ────────────────────────────────────────────────────────────

    @TypeConverter
    fun calidadLecheToString(value: CalidadLeche): String = value.name

    @TypeConverter
    fun stringToCalidadLeche(value: String): CalidadLeche = enumValueOf(value)

    // ── TipoVehiculo ────────────────────────────────────────────────────────────

    @TypeConverter
    fun tipoVehiculoToString(value: TipoVehiculo): String = value.name

    @TypeConverter
    fun stringToTipoVehiculo(value: String): TipoVehiculo = enumValueOf(value)
}
