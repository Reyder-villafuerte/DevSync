package com.example.holamundo.data.local.enums

/**
 * Modalidad de acopio de leche según el canal de recolección.
 *
 * @property etiqueta Texto legible para mostrar en la UI.
 */
enum class ModalidadAcopio(val etiqueta: String) {
    /** El productor entrega directamente en la planta municipal. */
    PLANTA("En Planta"),

    /** El acopiador viaja en vehículo a los establos de los sectores rurales. */
    RUTA("En Ruta")
}
