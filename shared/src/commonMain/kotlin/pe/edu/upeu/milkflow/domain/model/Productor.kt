package pe.edu.upeu.milkflow.domain.model

data class Productor(
    val id: String,
    val nombre: String,
    val activo: Boolean,
) {
    init {
        require(id.isNotBlank()) { "El identificador del productor es obligatorio." }
        require(nombre.isNotBlank()) { "El nombre del productor es obligatorio." }
    }
}
