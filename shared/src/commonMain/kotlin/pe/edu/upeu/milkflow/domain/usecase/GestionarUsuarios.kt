package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.domain.UsuarioNoEncontradoException
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository

class GestionarUsuarios(
    private val usuarioRepository: UsuarioRepository,
) {
    suspend fun registrar(usuario: Usuario): Usuario = usuarioRepository.guardar(usuario, null)

    suspend fun actualizar(usuario: Usuario): Usuario {
        usuarioRepository.obtenerPorId(usuario.id)
            ?: throw UsuarioNoEncontradoException(usuario.id)
        return usuarioRepository.guardar(usuario, null)
    }

    suspend fun cambiarEstado(usuarioId: String, activo: Boolean): Usuario {
        val usuario = usuarioRepository.obtenerPorId(usuarioId)
            ?: throw UsuarioNoEncontradoException(usuarioId)
        return usuarioRepository.guardar(usuario.copy(activo = activo), null)
    }

    fun observarTodos(): Flow<List<Usuario>> = usuarioRepository.observarTodos()
}
