package pe.edu.upeu.milkflow.domain.repository

import pe.edu.upeu.milkflow.domain.model.Acopiador

interface AcopiadorRepository {
    suspend fun obtenerPorId(id: String): Acopiador?
    suspend fun obtenerTodos(): List<Acopiador>
    suspend fun guardar(acopiador: Acopiador): Acopiador
}
