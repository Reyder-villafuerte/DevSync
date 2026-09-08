package pe.edu.upeu.milkflow.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.RangoFechas

interface LoteProduccionRepository {
    suspend fun guardar(lote: LoteProduccion): LoteProduccion
    suspend fun obtenerPorId(id: String): LoteProduccion?
    suspend fun obtenerPorRango(rango: RangoFechas): List<LoteProduccion>
    fun observarLotes(): Flow<List<LoteProduccion>>
}
