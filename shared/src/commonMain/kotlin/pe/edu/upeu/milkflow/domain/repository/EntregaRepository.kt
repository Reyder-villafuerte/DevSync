package pe.edu.upeu.milkflow.domain.repository

import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.RangoFechas

interface EntregaRepository {
    suspend fun obtenerPorId(id: String): Entrega?
    suspend fun obtenerTodas(): List<Entrega>
    suspend fun guardar(entrega: Entrega): Entrega
    suspend fun obtenerPorProductor(productorId: String, rango: RangoFechas? = null): List<Entrega>
    suspend fun obtenerPorRango(rango: RangoFechas): List<Entrega>
}
