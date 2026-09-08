package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository

data class ResumenCalidad(
    val muestrasHoy: Int,
    val adulteracionesHoy: Int,
    val rechazosHoy: Int,
)

class ObtenerResumenCalidad(
    private val calidadRepository: CalidadRepository,
) {
    suspend operator fun invoke(rango: RangoFechas): ResumenCalidad {
        val pruebas = calidadRepository.obtenerPruebasPorRango(rango)
        // Por ahora no hay lógica formal de adulteración/rechazo en el modelo
        return ResumenCalidad(
            muestrasHoy = pruebas.size,
            adulteracionesHoy = 0,
            rechazosHoy = 0
        )
    }
}
