package pe.edu.upeu.milkflow.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapRegistroSincronizable
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.RegistroSincronizable
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository

class SqlDelightSincronizacionRepository(
    database: MilkFlowDatabase,
    private val enviarRemoto: suspend (RegistroSincronizable) -> Boolean = { false },
) : SincronizacionRepository {
    private val queries = database.milkFlowQueries

    override fun observarRegistros(): Flow<List<RegistroSincronizable>> =
        queries.obtenerRegistrosSincronizacion(::mapRegistroSincronizable)
            .asFlow()
            .mapToList(Dispatchers.Default)

    override suspend fun obtenerRegistros(): List<RegistroSincronizable> =
        queries.obtenerRegistrosSincronizacion(::mapRegistroSincronizable).executeAsList()

    override fun observarPendientes(): Flow<List<RegistroSincronizable>> =
        queries.obtenerPendientes(::mapRegistroSincronizable)
            .asFlow()
            .mapToList(Dispatchers.Default)

    override suspend fun obtenerPendientes(): List<RegistroSincronizable> =
        queries.obtenerPendientes(::mapRegistroSincronizable).executeAsList()

    override suspend fun sincronizar(
        registro: RegistroSincronizable,
    ): EstadoSincronizacion {
        val estadoActual = obtenerEstado(registro.tipoRegistro, registro.registroId)
            ?: return EstadoSincronizacion.ERROR
        if (estadoActual == EstadoSincronizacion.ENVIADO) return EstadoSincronizacion.ENVIADO

        val estadoActualizado = try {
            if (enviarRemoto(registro)) {
                EstadoSincronizacion.ENVIADO
            } else {
                EstadoSincronizacion.ERROR
            }
        } catch (_: Exception) {
            EstadoSincronizacion.ERROR
        }

        actualizarEstado(registro.tipoRegistro, registro.registroId, estadoActualizado)
        return estadoActualizado
    }

    override suspend fun obtenerEstado(
        tipoRegistro: String,
        registroId: String,
    ): EstadoSincronizacion? = queries.obtenerEstadoSincronizacion(
        tipo_registro = tipoRegistro,
        registro_id = registroId,
    ).executeAsOneOrNull()?.let(EstadoSincronizacion::valueOf)

    override suspend fun actualizarEstado(
        tipoRegistro: String,
        registroId: String,
        estado: EstadoSincronizacion,
    ): Boolean {
        if (obtenerEstado(tipoRegistro, registroId) == null) return false

        queries.transaction {
            queries.actualizarEstadoSincronizacion(
                estado = estado.name,
                tipo_registro = tipoRegistro,
                registro_id = registroId,
            )
            if (tipoRegistro == SqlDelightEntregaRepository.TIPO_ENTREGA) {
                queries.actualizarEstadoEntrega(estado.name, registroId)
            }
        }
        return true
    }
}
