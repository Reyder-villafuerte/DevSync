package pe.edu.upeu.milkflow.data.repository

import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapProblemaLeche
import pe.edu.upeu.milkflow.data.mapper.mapPruebaCalidad
import pe.edu.upeu.milkflow.domain.EntregaNoEncontradaException
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository

class SqlDelightCalidadRepository(
    database: MilkFlowDatabase,
) : CalidadRepository {
    private val queries = database.milkFlowQueries

    override suspend fun obtenerPruebaPorId(id: String): PruebaCalidad? =
        queries.obtenerPruebaPorId(id, ::mapPruebaCalidad).executeAsOneOrNull()

    override suspend fun obtenerPruebaPorEntrega(entregaId: String): PruebaCalidad? =
        queries.obtenerPruebaPorEntrega(entregaId, ::mapPruebaCalidad).executeAsOneOrNull()

    override suspend fun obtenerProblemasPorEntrega(entregaId: String): List<ProblemaLeche> =
        queries.obtenerProblemasPorEntrega(entregaId, ::mapProblemaLeche).executeAsList()

    override suspend fun obtenerPruebasPorRango(rango: RangoFechas): List<PruebaCalidad> =
        queries.obtenerPruebasPorRango(
            fecha_hora_epoch_millis = rango.inicio.toEpochMilliseconds(),
            fecha_hora_epoch_millis_ = rango.finExclusivo.toEpochMilliseconds(),
            mapper = ::mapPruebaCalidad
        ).executeAsList()

    override suspend fun obtenerProblemasPorRango(rango: RangoFechas): List<ProblemaLeche> =
        queries.obtenerProblemasPorRango(
            fecha_hora_epoch_millis = rango.inicio.toEpochMilliseconds(),
            fecha_hora_epoch_millis_ = rango.finExclusivo.toEpochMilliseconds(),
            mapper = ::mapProblemaLeche
        ).executeAsList()

    override suspend fun obtenerTodosLosProblemas(): List<ProblemaLeche> =
        queries.obtenerTodosLosProblemas(::mapProblemaLeche).executeAsList()

    override suspend fun obtenerTodasLasPruebas(): List<PruebaCalidad> =
        queries.obtenerTodasLasPruebas(::mapPruebaCalidad).executeAsList()

    override suspend fun guardarPrueba(prueba: PruebaCalidad): PruebaCalidad {
        validarEntrega(prueba.entregaId)
        queries.guardarPruebaCalidad(
            id = prueba.id,
            entrega_id = prueba.entregaId,
            fecha_hora_epoch_millis = prueba.fechaHora.toEpochMilliseconds()
        )
        return prueba
    }

    override suspend fun guardarProblema(problema: ProblemaLeche): ProblemaLeche {
        validarEntrega(problema.entregaId)
        queries.guardarProblemaLeche(
            id = problema.id,
            entrega_id = problema.entregaId,
            descripcion = problema.descripcion,
            fecha_hora_epoch_millis = problema.fechaHora.toEpochMilliseconds()
        )
        return problema
    }

    private fun validarEntrega(entregaId: String) {
        val existe = queries.obtenerEntregaPorId(entregaId) { _, _, _, _, _, _, _, _, _ -> true }
            .executeAsOneOrNull() ?: false
        if (!existe) throw EntregaNoEncontradaException(entregaId)
    }
}
