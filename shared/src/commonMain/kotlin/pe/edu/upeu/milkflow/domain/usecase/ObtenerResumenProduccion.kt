package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.LoteProduccionRepository

data class ResumenProduccion(
    val lecheProcesada: Double,
    val quesosProducidos: Int,
    val rendimientoPromedio: Double?,
)

class ObtenerResumenProduccion(
    private val repository: LoteProduccionRepository,
) {
    suspend operator fun invoke(rango: RangoFechas): ResumenProduccion {
        val lotes = repository.obtenerPorRango(rango)
        val lecheTotal = lotes.sumOf { it.litrosLecheUtilizados }
        val quesosTotal = lotes.filter { it.tipoProducto.esQueso() }.sumOf { it.moldesObtenidos }
        
        val lotesDeQueso = lotes.filter { it.tipoProducto.esQueso() }
        val lecheParaQueso = lotesDeQueso.sumOf { it.litrosLecheUtilizados }
        
        val rendimiento = if (lecheParaQueso > 0) {
            (quesosTotal.toDouble() / lecheParaQueso) * 100.0
        } else null

        return ResumenProduccion(
            lecheProcesada = lecheTotal,
            quesosProducidos = quesosTotal,
            rendimientoPromedio = rendimiento
        )
    }
}
