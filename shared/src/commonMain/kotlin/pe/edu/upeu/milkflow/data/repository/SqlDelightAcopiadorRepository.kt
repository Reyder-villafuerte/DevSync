package pe.edu.upeu.milkflow.data.repository

import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapAcopiador
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository

class SqlDelightAcopiadorRepository(
    database: MilkFlowDatabase,
) : AcopiadorRepository {
    private val queries = database.milkFlowQueries

    override suspend fun obtenerPorId(id: String): Acopiador? =
        queries.obtenerAcopiadorPorId(id, ::mapAcopiador).executeAsOneOrNull()

    override suspend fun obtenerTodos(): List<Acopiador> =
        queries.obtenerAcopiadores(::mapAcopiador).executeAsList()

    override suspend fun guardar(acopiador: Acopiador): Acopiador {
        queries.guardarAcopiador(acopiador.id, acopiador.nombre)
        return acopiador
    }
}
