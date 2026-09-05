package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository

class RegistrarCuenta(
    private val usuarioRepository: UsuarioRepository,
) {
    suspend operator fun invoke(
        id: String,
        nombreCompleto: String,
        correo: String,
        clave: String,
    ): Usuario {
        val usuario = Usuario(
            id = id,
            nombreUsuario = correo,
            nombre = nombreCompleto,
            rol = RolUsuario.PENDIENTE_ASIGNACION,
            activo = true
        )
        return usuarioRepository.guardar(usuario, clave)
    }
}
