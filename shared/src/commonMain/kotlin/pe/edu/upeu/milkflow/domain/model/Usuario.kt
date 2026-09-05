package pe.edu.upeu.milkflow.domain.model

data class Usuario(
    val id: String,
    val nombreUsuario: String,
    val nombre: String,
    val rol: RolUsuario,
    val activo: Boolean,
) {
    init {
        require(id.isNotBlank()) { "El identificador del usuario es obligatorio." }
        require(nombreUsuario.isNotBlank()) { "El nombre de usuario es obligatorio." }
        require(nombre.isNotBlank()) { "El nombre del usuario es obligatorio." }
    }
}
