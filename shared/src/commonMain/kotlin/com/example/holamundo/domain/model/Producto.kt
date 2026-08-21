package com.example.holamundo.domain.model

/**
 * Entidad Producto del dominio.
 *
 * Es una data class porque su único propósito es transportar datos.
 * Kotlin genera automáticamente equals(), hashCode(), toString() y copy().
 */
data class Producto(
    val id: String,
    val nombre: String,
    val precio: Double,
    val categoria: Categoria,
    val descripcion: String? = null,
    val disponible: Boolean = true
) {
    /** Operador Elvis: si descripcion es null, usa el texto por defecto. */
    val descripcionCorta: String
        get() = descripcion ?: "Sin descripción"

    /** Regla de negocio: no se puede pedir lo agotado ni lo que no tiene precio. */
    val sePuedePedir: Boolean
        get() = disponible && precio > 0

    /** Texto listo para mostrar en la futura interfaz. */
    val precioFormateado: String
        get() = "S/ $precio"
}