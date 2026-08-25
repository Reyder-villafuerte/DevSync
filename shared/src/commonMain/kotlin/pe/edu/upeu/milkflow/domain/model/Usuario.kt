package pe.edu.upeu.milkflow.domain.model

/**
 * Representa a un usuario que interactúa con la aplicación.
 */
data class Usuario(
    val id: String,
    val username: String,
    val rol: RolUsuario
)
