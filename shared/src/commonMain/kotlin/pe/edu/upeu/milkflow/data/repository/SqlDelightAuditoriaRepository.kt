package pe.edu.upeu.milkflow.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.mapper.mapAuditoria
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository

class SqlDelightAuditoriaRepository(
    database: MilkFlowDatabase,
) : AuditoriaRepository {
    private val queries = database.milkFlowQueries

    override suspend fun obtenerPorId(id: String): RegistroAuditoria? =
        queries.obtenerAuditoriaPorId(id, ::mapAuditoria).executeAsOneOrNull()

    override suspend fun guardar(registro: RegistroAuditoria): RegistroAuditoria {
        queries.guardarAuditoria(
            id = registro.id,
            usuario_id = registro.usuarioId,
            fecha_hora_epoch_millis = registro.fechaHora.toEpochMilliseconds(),
            registro_afectado_id = registro.registroAfectadoId,
            accion = registro.accion,
        )
        return registro
    }

    override fun observarTodos(): Flow<List<RegistroAuditoria>> =
        queries.obtenerAuditorias(::mapAuditoria)
            .asFlow()
            .mapToList(Dispatchers.Default)
}
