package pe.edu.upeu.milkflow.domain.model

data class Acopiador(
    val id: String,
    val nombre: String,
) {
    init {
        require(id.isNotBlank()) { "El identificador del acopiador es obligatorio." }
        require(nombre.isNotBlank()) { "El nombre del acopiador es obligatorio." }
    }
}
