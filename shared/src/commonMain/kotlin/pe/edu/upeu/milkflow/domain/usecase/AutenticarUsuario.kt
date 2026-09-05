package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.CredencialesInvalidasException
import pe.edu.upeu.milkflow.domain.CuentaPendienteException
import pe.edu.upeu.milkflow.domain.UsuarioInactivoException
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository

/**
 * Implementa RF-01 como autenticación, de acuerdo con el nombre funcional del requisito.
 *
 * La matriz original mezcla este requisito con criterios de registro de productor. Esa
 * inconsistencia se conserva documentada y requiere revisión antes de ampliar este caso de uso.
 */
class AutenticarUsuario(
    private val usuarioRepository: UsuarioRepository,
) {
    suspend operator fun invoke(nombreUsuario: String, clave: String): Usuario {
        if (nombreUsuario.isBlank() || clave.isBlank()) {
            throw CredencialesInvalidasException()
        }

        val usuario = usuarioRepository.autenticar(nombreUsuario, clave)
            ?: throw CredencialesInvalidasException()

        if (!usuario.activo) {
            throw UsuarioInactivoException(usuario.id)
        }

        if (usuario.rol == RolUsuario.PENDIENTE_ASIGNACION) {
            throw CuentaPendienteException()
        }

        return usuario
    }
}
