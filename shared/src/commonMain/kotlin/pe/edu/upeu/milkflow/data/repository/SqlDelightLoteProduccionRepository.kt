package pe.edu.upeu.milkflow.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapLoteProduccion
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.LoteProduccionRepository

class SqlDelightLoteProduccionRepository(
    database: MilkFlowDatabase,
) : LoteProduccionRepository {
    private val queries = database.milkFlowQueries

    override suspend fun guardar(lote: LoteProduccion): LoteProduccion {
        queries.guardarLoteProduccion(
            id = lote.id,
            fecha_hora_epoch_millis = lote.fechaHora.toEpochMilliseconds(),
            litros_utilizados = lote.litrosLecheUtilizados,
            moldes_obtenidos = lote.moldesObtenidos.toLong(),
            tipo_producto = lote.tipoProducto.name,
            observaciones = lote.observaciones
        )
        return lote
    }

    override suspend fun obtenerPorId(id: String): LoteProduccion? =
        queries.obtenerLotePorId(id, ::mapLoteProduccion).executeAsOneOrNull()

    override suspend fun obtenerPorRango(rango: RangoFechas): List<LoteProduccion> =
        queries.obtenerLotesProduccionPorRango(
            fecha_hora_epoch_millis = rango.inicio.toEpochMilliseconds(),
            fecha_hora_epoch_millis_ = rango.finExclusivo.toEpochMilliseconds(),
            mapper = ::mapLoteProduccion
        ).executeAsList()

    override fun observarLotes(): Flow<List<LoteProduccion>> =
        queries.obtenerLotesProduccion(::mapLoteProduccion)
            .asFlow()
            .mapToList(Dispatchers.Default)
}
