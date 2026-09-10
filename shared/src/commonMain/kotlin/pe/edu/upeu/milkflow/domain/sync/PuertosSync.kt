package pe.edu.upeu.milkflow.domain.sync

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito

/**
 * Persistencia local del estado de sincronización: outbox, cursores por ámbito
 * y contadores observables. Implementada en la capa data sobre SQLDelight.
 */
interface SincronizacionRepository {

    // ---- Outbox ----
    /** Siguiente lote de operaciones pendientes, en orden de creación. */
    suspend fun siguienteLote(limite: Int): List<OperacionOutbox>

    /** Marca la operación como confirmada por el servidor y actualiza la fila local. */
    suspend fun marcarSincronizada(idLocal: Long, versionServidor: Long)

    /** Suma un intento fallido (reintentable). */
    suspend fun registrarIntentoFallido(idLocal: Long, error: String)

    /** Marca la operación como conflicto no resuelto y expone el estado del servidor. */
    suspend fun marcarConflicto(idLocal: Long, motivo: String, servidorJson: String?)

    /** Descarta una operación (p. ej. conmutativa ya presente en el servidor). */
    suspend fun descartar(idLocal: Long)

    fun observarPendientes(): Flow<Int>
    fun observarConflictos(): Flow<List<OperacionOutbox>>

    // ---- Cursor de bajada, por ámbito ----
    suspend fun cursor(ambito: Ambito): String?
    suspend fun guardarCursor(ambito: Ambito, cursor: String)

    // ---- Estado observable para la UI ----
    fun observarEstado(): Flow<EstadoSincronizacion>
    suspend fun marcarSincronizando(activo: Boolean)
    suspend fun registrarExito(instanteIso: String)
}

/**
 * Pasarela remota de sincronización (capa data, sobre Ktor).
 *
 * `bajar` NO devuelve las filas: las persiste en local de forma transaccional
 * (vía el aplicador de cambios) y entrega solo el resumen. Así el dominio no
 * necesita conocer la forma de los DTO ni de la BD.
 */
interface ClienteSincronizacion {
    suspend fun subir(operaciones: List<OperacionOutbox>): Resultado<ResultadoSubida>
    suspend fun bajar(desde: String?, ambito: Ambito): Resultado<ResumenBajada>
}

/**
 * Observador de conectividad multiplataforma. Emite `true` cuando hay una red
 * utilizable. El SyncManager se suscribe para disparar la sincronización al
 * recuperar red.
 */
interface ObservadorConectividad {
    val enLinea: Flow<Boolean>
}
