package pe.edu.upeu.milkflow.domain.repository

import pe.edu.upeu.milkflow.domain.model.Productor

interface ProductorRepository {
    suspend fun obtenerPorId(id: String): Productor?
    suspend fun obtenerTodos(): List<Productor>
    suspend fun guardar(productor: Productor): Productor
}
