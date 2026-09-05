package pe.edu.upeu.milkflow.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria

interface AuditoriaRepository {
    suspend fun obtenerPorId(id: String): RegistroAuditoria?
    suspend fun guardar(registro: RegistroAuditoria): RegistroAuditoria
    fun observarTodos(): Flow<List<RegistroAuditoria>>
}
