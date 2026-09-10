package pe.edu.upeu.milkflow.domain.vo

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado

/**
 * Lectura del equipo Lactoscan que el supervisor toma en un control inopinado.
 * Es un value object: sin identidad, inmutable y auto-validado. El use case
 * EvaluarCalidad opera sobre esto para producir el dictamen (RN-05 / RN-06)
 * SIN necesidad de red.
 *
 * `aguaAnadidaPorcentaje` y `ph` son opcionales por separado, pero al menos uno
 * debe venir para poder dictaminar.
 */
data class MedicionLactoscan(
    val aguaAnadidaPorcentaje: Double?,
    val ph: Double?,
    val densidad: Double? = null,
    val grasaPorcentaje: Double? = null,
    val solidosNoGrasosPorcentaje: Double? = null,
    val temperatura: Double? = null,
) {
    val tieneDatosSuficientes: Boolean
        get() = aguaAnadidaPorcentaje != null || ph != null

    companion object {
        fun de(
            aguaAnadidaPorcentaje: Double?,
            ph: Double?,
            densidad: Double? = null,
            grasaPorcentaje: Double? = null,
            solidosNoGrasosPorcentaje: Double? = null,
            temperatura: Double? = null,
        ): Resultado<MedicionLactoscan> {
            if (aguaAnadidaPorcentaje != null && aguaAnadidaPorcentaje !in 0.0..100.0) {
                return Resultado.Fallo(ErrorApp.Validacion("Agua añadida", "porcentaje fuera de 0–100"))
            }
            if (ph != null && ph !in 0.0..14.0) {
                return Resultado.Fallo(ErrorApp.Validacion("pH", "fuera de 0–14"))
            }
            val m = MedicionLactoscan(aguaAnadidaPorcentaje, ph, densidad, grasaPorcentaje, solidosNoGrasosPorcentaje, temperatura)
            if (!m.tieneDatosSuficientes) {
                return Resultado.Fallo(ErrorApp.Validacion("Medición", "ingrese al menos agua añadida o pH"))
            }
            return Resultado.Exito(m)
        }
    }
}
