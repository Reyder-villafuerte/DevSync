package pe.edu.upeu.milkflow.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.RegistroSincronizable

interface SincronizacionRepository {
    fun observarRegistros(): Flow<List<RegistroSincronizable>>
    suspend fun obtenerRegistros(): List<RegistroSincronizable>
    fun observarPendientes(): Flow<List<RegistroSincronizable>>
    suspend fun obtenerPendientes(): List<RegistroSincronizable>
    suspend fun sincronizar(registro: RegistroSincronizable): EstadoSincronizacion
    suspend fun obtenerEstado(tipoRegistro: String, registroId: String): EstadoSincronizacion?
    suspend fun actualizarEstado(
        tipoRegistro: String,
        registroId: String,
        estado: EstadoSincronizacion,
    ): Boolean
}
