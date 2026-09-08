package pe.edu.upeu.milkflow.domain.repository

import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RangoFechas

interface CalidadRepository {
    suspend fun obtenerPruebaPorId(id: String): PruebaCalidad?
    suspend fun obtenerPruebaPorEntrega(entregaId: String): PruebaCalidad?
    suspend fun obtenerProblemasPorEntrega(entregaId: String): List<ProblemaLeche>
    suspend fun obtenerPruebasPorRango(rango: RangoFechas): List<PruebaCalidad>
    suspend fun obtenerProblemasPorRango(rango: RangoFechas): List<ProblemaLeche>
    suspend fun obtenerTodasLasPruebas(): List<PruebaCalidad>
    suspend fun obtenerTodosLosProblemas(): List<ProblemaLeche>
    suspend fun guardarPrueba(prueba: PruebaCalidad): PruebaCalidad
    suspend fun guardarProblema(problema: ProblemaLeche): ProblemaLeche
}
