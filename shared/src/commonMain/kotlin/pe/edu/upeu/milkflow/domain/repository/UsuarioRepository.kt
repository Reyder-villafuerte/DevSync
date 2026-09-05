package pe.edu.upeu.milkflow.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.domain.model.Usuario

interface UsuarioRepository {
    suspend fun autenticar(nombreUsuario: String, clave: String): Usuario?
    suspend fun obtenerPorId(id: String): Usuario?
    suspend fun guardar(usuario: Usuario, clave: String? = null): Usuario
    fun observarTodos(): Flow<List<Usuario>>
}
