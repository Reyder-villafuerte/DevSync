package com.example.holamundo.data.local.enums

/**
 * Resultado del control de calidad de cada entrega de leche.
 *
 * @property etiqueta    Texto legible para mostrar en la UI.
 * @property esAceptable Indica si la leche puede ingresar al proceso productivo.
 */
enum class CalidadLeche(val etiqueta: String, val esAceptable: Boolean) {
    /** Leche en condiciones óptimas. Ingresa directamente al proceso productivo. */
    CONFORME("Conforme", esAceptable = true),

    /** Leche con observación menor. Requiere revisión antes de procesarla. */
    OBSERVADO("Observado", esAceptable = true),

    /**
     * Leche no apta. Se registra el rechazo pero los litros NO
     * se contabilizan en el total del día.
     */
    RECHAZADO("Rechazado", esAceptable = false)
}
