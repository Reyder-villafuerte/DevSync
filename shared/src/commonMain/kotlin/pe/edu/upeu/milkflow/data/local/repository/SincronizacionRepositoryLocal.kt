package pe.edu.upeu.milkflow.data.local.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import app.cash.sqldelight.coroutines.mapToOne
import app.cash.sqldelight.coroutines.mapToOneOrNull
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.edu.upeu.milkflow.data.local.aDominio
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.sync.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.sync.OperacionOutbox
import pe.edu.upeu.milkflow.domain.sync.SincronizacionRepository
import kotlin.time.Instant

/**
 * Implementación local del estado de sincronización sobre SQLDelight.
 *
 * IMPORTANTE — el `encolar` NO se llama desde aquí: lo llaman los repositorios
 * de negocio DENTRO de su propia transacción. Así la escritura de negocio y su
 * operación de outbox son atómicas: nunca hay una recolección sin su entrada en
 * el outbox ni viceversa.
 *
 * Nota SQLDelight 2.3: los mutadores devuelven `QueryResult<Long>`; se usan
 * cuerpos de bloque (sin `=`) para que el override respete el retorno `Unit`
 * del contrato de dominio.
 */
class SincronizacionRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : SincronizacionRepository {

    private val q get() = db.milkFlowQueries

    override suspend fun siguienteLote(limite: Int): List<OperacionOutbox> = withContext(io) {
        q.lotePendiente(limite.toLong()).executeAsList().map { it.aDominio() }
    }

    override suspend fun marcarSincronizada(idLocal: Long, versionServidor: Long) {
        // La verdad definitiva (version, updated_at) llegará en la bajada
        // inmediatamente posterior; aquí basta con sacar la operación del outbox.
        withContext(io) { q.eliminarOutbox(idLocal) }
    }

    override suspend fun registrarIntentoFallido(idLocal: Long, error: String) {
        withContext(io) { q.marcarIntento(error, idLocal) }
    }

    override suspend fun marcarConflicto(idLocal: Long, motivo: String, servidorJson: String?) {
        withContext(io) { q.marcarConflictoOutbox(motivo, servidorJson, idLocal) }
    }

    override suspend fun descartar(idLocal: Long) {
        withContext(io) { q.eliminarOutbox(idLocal) }
    }

    override fun observarPendientes(): Flow<Int> =
        q.contarPendientes().asFlow().mapToOne(io).map { it.toInt() }

    override fun observarConflictos(): Flow<List<OperacionOutbox>> =
        q.conflictos().asFlow().mapToList(io).map { filas -> filas.map { it.aDominio() } }

    override suspend fun cursor(ambito: Ambito): String? = withContext(io) {
        q.cursorPorAmbito(ambito.parametro).executeAsOneOrNull()
    }

    override suspend fun guardarCursor(ambito: Ambito, cursor: String) {
        withContext(io) { q.guardarCursor(ambito.parametro, cursor) }
    }

    override fun observarEstado(): Flow<EstadoSincronizacion> =
        q.estadoValor("ultimo_exito").asFlow().mapToOneOrNull(io).map { fila ->
            val iso = fila?.valor
            EstadoSincronizacion(ultimoExito = iso?.let { runCatching { Instant.parse(it) }.getOrNull() })
        }

    override suspend fun marcarSincronizando(activo: Boolean) {
        withContext(io) { q.guardarEstado("sincronizando", activo.toString()) }
    }

    override suspend fun registrarExito(instanteIso: String) {
        withContext(io) { q.guardarEstado("ultimo_exito", instanteIso) }
    }
}
