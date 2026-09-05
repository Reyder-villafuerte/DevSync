package pe.edu.upeu.milkflow.data.repository

import pe.edu.upeu.milkflow.domain.AcopiadorNoEncontradoException
import pe.edu.upeu.milkflow.domain.ProductorInactivoException
import pe.edu.upeu.milkflow.domain.ProductorNoEncontradoException
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapEntrega
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository

class SqlDelightEntregaRepository(
    database: MilkFlowDatabase,
) : EntregaRepository {
    private val queries = database.milkFlowQueries

    override suspend fun obtenerPorId(id: String): Entrega? =
        queries.obtenerEntregaPorId(id, ::mapEntrega).executeAsOneOrNull()

    override suspend fun obtenerTodas(): List<Entrega> =
        queries.obtenerEntregas(::mapEntrega).executeAsList()

    override suspend fun guardar(entrega: Entrega): Entrega {
        val productor = queries.obtenerProductorPorId(entrega.productorId) { id, nombre, activo ->
            Triple(id, nombre, activo == 1L)
        }.executeAsOneOrNull() ?: throw ProductorNoEncontradoException(entrega.productorId)

        if (!productor.third) {
            throw ProductorInactivoException(entrega.productorId)
        }

        if (entrega.tipo == TipoEntrega.RECOGIDA) {
            val acopiadorId = entrega.acopiadorId
                ?: throw AcopiadorNoEncontradoException("")
            val existe = queries.obtenerAcopiadorPorId(acopiadorId) { _, _ -> true }
                .executeAsOneOrNull() ?: false
            if (!existe) throw AcopiadorNoEncontradoException(acopiadorId)
        }

        queries.transaction {
            queries.guardarEntrega(
                id = entrega.id,
                productor_id = entrega.productorId,
                fecha_hora_epoch_millis = entrega.fechaHora.toEpochMilliseconds(),
                litros = entrega.litros,
                tipo = entrega.tipo.name,
                usuario_registro_id = entrega.usuarioRegistroId,
                estado_sincronizacion = entrega.estadoSincronizacion.name,
                acopiador_id = entrega.acopiadorId,
                sector = entrega.sector,
            )
            queries.guardarEstadoSincronizacion(
                tipo_registro = TIPO_ENTREGA,
                registro_id = entrega.id,
                estado = entrega.estadoSincronizacion.name,
            )
        }
        return entrega
    }

    override suspend fun obtenerPorProductor(
        productorId: String,
        rango: RangoFechas?,
    ): List<Entrega> = if (rango == null) {
        queries.obtenerEntregasPorProductor(productorId, ::mapEntrega).executeAsList()
    } else {
        queries.obtenerEntregasPorProductorYRango(
            productor_id = productorId,
            fecha_hora_epoch_millis = rango.inicio.toEpochMilliseconds(),
            fecha_hora_epoch_millis_ = rango.finExclusivo.toEpochMilliseconds(),
            mapper = ::mapEntrega,
        ).executeAsList()
    }

    override suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega> =
        queries.obtenerEntregasPorRango(
            fecha_hora_epoch_millis = rango.inicio.toEpochMilliseconds(),
            fecha_hora_epoch_millis_ = rango.finExclusivo.toEpochMilliseconds(),
            mapper = ::mapEntrega,
        ).executeAsList()

    companion object {
        const val TIPO_ENTREGA = "ENTREGA"
    }
}
