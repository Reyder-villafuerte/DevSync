package pe.edu.upeu.milkflow.data.repository

import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapProductor
import pe.edu.upeu.milkflow.data.mapper.toSqlLong
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository

class SqlDelightProductorRepository(
    database: MilkFlowDatabase,
) : ProductorRepository {
    private val queries = database.milkFlowQueries

    override suspend fun obtenerPorId(id: String): Productor? =
        queries.obtenerProductorPorId(id, ::mapProductor).executeAsOneOrNull()

    override suspend fun obtenerTodos(): List<Productor> =
        queries.obtenerProductores(::mapProductor).executeAsList()

    override suspend fun guardar(productor: Productor): Productor {
        queries.guardarProductor(productor.id, productor.nombre, productor.activo.toSqlLong())
        return productor
    }
}
