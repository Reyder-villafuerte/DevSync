package pe.edu.upeu.milkflow.domain.calidad

import pe.edu.upeu.milkflow.domain.model.DictamenCalidad
import pe.edu.upeu.milkflow.domain.vo.MedicionLactoscan

/**
 * Reglas de calidad RN-05 (adulteración con agua) y RN-06 (acidez), evaluadas
 * EN EL CLIENTE para que el supervisor obtenga el dictamen sin red.
 *
 * Debe dar exactamente el mismo veredicto que EvaluacionCalidadService del
 * backend; el backend re-evalúa al recibir la inspección y su dictamen manda,
 * pero en la práctica coinciden.
 *
 * Objeto sin estado ni dependencias: es lógica pura y determinista.
 */
class EvaluadorCalidad(
    // Umbrales parametrizables (por defecto los de config/milkflow.php).
    private val umbralExpulsionAguaPct: Double = 5.0,
    private val phMinimo: Double = 6.5,
) {

    data class Resultado(
        val dictamen: DictamenCalidad,
        val detalle: String,
        val rechazaLote: Boolean,
        val esReincidencia: Boolean,
        val reglaAplicada: String,
    )

    /**
     * @param medicion lectura del Lactoscan.
     * @param tieneSancionAguaPrevia si el productor ya arrastra una sanción de
     *        agua vigente (lo sabe el repositorio local a partir de sanciones ya
     *        sincronizadas). Determina primera infracción vs. reincidencia en RN-05.
     */
    fun evaluar(medicion: MedicionLactoscan, tieneSancionAguaPrevia: Boolean): Resultado {
        val agua = medicion.aguaAnadidaPorcentaje ?: 0.0
        val ph = medicion.ph

        // ---- RN-05: adulteración con agua (la más grave, se evalúa primero) ----
        when {
            agua >= umbralExpulsionAguaPct -> return Resultado(
                dictamen = DictamenCalidad.EXPULSION_AGUA,
                detalle = "Agua añadida ${agua}% ≥ ${umbralExpulsionAguaPct}%: expulsión inmediata y pago degradado a tarifa mínima.",
                rechazaLote = true,
                esReincidencia = tieneSancionAguaPrevia,
                reglaAplicada = "RN-05 ≥ ${umbralExpulsionAguaPct}%",
            )

            agua > 0.0 && tieneSancionAguaPrevia -> return Resultado(
                dictamen = DictamenCalidad.DESCUENTO_RETIRO_AGUA,
                detalle = "Agua añadida ${agua}% con reincidencia: descuento en la liquidación y retiro definitivo del padrón.",
                rechazaLote = false,
                esReincidencia = true,
                reglaAplicada = "RN-05 < ${umbralExpulsionAguaPct}% (reincidencia)",
            )

            agua > 0.0 -> return Resultado(
                dictamen = DictamenCalidad.ADVERTENCIA_AGUA,
                detalle = "Agua añadida ${agua}%: advertencia y descuento en la liquidación de esta semana.",
                rechazaLote = false,
                esReincidencia = false,
                reglaAplicada = "RN-05 < ${umbralExpulsionAguaPct}% (primera infracción)",
            )
        }

        // ---- RN-06: acidez ----
        if (ph != null && ph < phMinimo) {
            return Resultado(
                dictamen = DictamenCalidad.RECHAZADO_ACIDEZ,
                detalle = "pH $ph < $phMinimo: lote rechazado y derivación a capacitación obligatoria en Buenas Prácticas de Ordeño (sin expulsión).",
                rechazaLote = true,
                esReincidencia = false,
                reglaAplicada = "RN-06 pH < $phMinimo",
            )
        }

        return Resultado(
            dictamen = DictamenCalidad.APROBADO,
            detalle = "Dentro de parámetros.",
            rechazaLote = false,
            esReincidencia = false,
            reglaAplicada = "—",
        )
    }
}
