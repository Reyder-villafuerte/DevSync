package pe.edu.upeu.milkflow.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapUsuario
import pe.edu.upeu.milkflow.data.mapper.toSqlLong
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository

/**
 * La verificación de la clave se inyecta porque el modelo de Domain no define
 * todavía cómo se crean, cifran o almacenan credenciales.
 */
class SqlDelightUsuarioRepository(
    database: MilkFlowDatabase,
    private val validarClave: suspend (usuarioId: String, clave: String) -> Boolean,
) : UsuarioRepository {
    private val queries = database.milkFlowQueries

    override suspend fun autenticar(nombreUsuario: String, clave: String): Usuario? {
        val result = queries.obtenerUsuarioPorNombreConClave(nombreUsuario).executeAsOneOrNull()
            ?: return null

        val usuario = mapUsuario(
            id = result.id,
            nombreUsuario = result.nombre_usuario,
            nombre = result.nombre,
            rol = result.rol,
            activo = result.activo,
        )

        // Validamos usando la clave guardada localmente O el validador inyectado
        val esValida = (result.clave != null && result.clave == clave) ||
            validarClave(usuario.id, clave)

        return usuario.takeIf { it.activo && esValida }
    }

    override suspend fun obtenerPorId(id: String): Usuario? =
        queries.obtenerUsuarioPorId(id, ::mapUsuario).executeAsOneOrNull()

    override suspend fun guardar(usuario: Usuario, clave: String?): Usuario {
        queries.guardarUsuario(
            id = usuario.id,
            nombre_usuario = usuario.nombreUsuario,
            nombre = usuario.nombre,
            rol = usuario.rol.name,
            activo = usuario.activo.toSqlLong(),
            clave = clave
        )
        return usuario
    }

    override fun observarTodos(): Flow<List<Usuario>> =
        queries.obtenerUsuarios(::mapUsuario)
            .asFlow()
            .mapToList(Dispatchers.Default)
}
