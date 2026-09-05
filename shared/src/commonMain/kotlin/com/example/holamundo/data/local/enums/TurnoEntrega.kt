package com.example.holamundo.data.local.enums

/**
 * Turno de recolección de leche del día.
 *
 * @property etiqueta Texto legible para mostrar en la UI.
 */
enum class TurnoEntrega(val etiqueta: String) {
    /** Turno matutino: normalmente entre 05:00 y 10:00 hrs. */
    MANANA("Mañana"),

    /** Turno vespertino: normalmente entre 15:00 y 19:00 hrs. */
    TARDE("Tarde")
}
