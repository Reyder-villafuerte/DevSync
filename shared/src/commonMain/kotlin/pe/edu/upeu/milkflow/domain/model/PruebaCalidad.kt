package pe.edu.upeu.milkflow.domain.model

import pe.edu.upeu.milkflow.domain.PruebaCalidadInvalidaException

/**
 * Representación mínima de una prueba de calidad.
 *
 * Las mediciones de laboratorio se añadirán cuando los requisitos las definan.
 */
data class PruebaCalidad(
    val id: String,
    val entregaId: String,
) {
    init {
        require(id.isNotBlank()) { "El identificador de la prueba es obligatorio." }
        if (entregaId.isBlank()) {
            throw PruebaCalidadInvalidaException(
                "La prueba de calidad debe estar vinculada a una entrega.",
            )
        }
    }
}

data class ProblemaLeche(
    val id: String,
    val entregaId: String,
    val descripcion: String,
) {
    init {
        require(id.isNotBlank()) { "El identificador del problema es obligatorio." }
        require(entregaId.isNotBlank()) { "El problema debe estar vinculado a una entrega." }
        require(descripcion.isNotBlank()) { "La descripción del problema es obligatoria." }
    }
}
