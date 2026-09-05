package pe.edu.upeu.milkflow.domain.repository

import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad

interface CalidadRepository {
    suspend fun obtenerPruebaPorId(id: String): PruebaCalidad?
    suspend fun obtenerPruebaPorEntrega(entregaId: String): PruebaCalidad?
    suspend fun obtenerProblemasPorEntrega(entregaId: String): List<ProblemaLeche>
    suspend fun guardarPrueba(prueba: PruebaCalidad): PruebaCalidad
    suspend fun guardarProblema(problema: ProblemaLeche): ProblemaLeche
}
