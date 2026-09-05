package com.example.holamundo.data.local.enums

/**
 * Tipo de vehículo utilizado para el acopio en ruta.
 *
 * @property etiqueta Texto legible para mostrar en la UI.
 */
enum class TipoVehiculo(val etiqueta: String) {
    /** Camión de mayor capacidad para rutas con acceso vial habilitado. */
    CAMION("Camión"),

    /** Motofurgón ágil para trochas y sectores de difícil acceso. */
    MOTOFURGON("Motofurgón")
}
