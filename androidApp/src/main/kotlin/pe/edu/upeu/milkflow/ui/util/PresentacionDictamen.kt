package pe.edu.upeu.milkflow.ui.util

import pe.edu.upeu.milkflow.domain.model.DictamenCalidad

/**
 * Presentación (NO recálculo) de un dictamen ya emitido por el EvaluadorCalidad:
 * titular del veredicto, tarifa de liquidación resultante, situación en el
 * padrón y la etiqueta del botón de acción ejecutable.
 */
data class PresentacionDictamen(
    val titular: String,
    val tarifaResultante: String,
    val situacionPadron: String,
    val etiquetaAccion: String,
)

fun presentacionDe(dictamen: DictamenCalidad): PresentacionDictamen = when (dictamen) {
    DictamenCalidad.APROBADO -> PresentacionDictamen(
        titular = "APROBADO",
        tarifaResultante = "Tarifa normal de compra de leche",
        situacionPadron = "Sin cambios en el padrón",
        etiquetaAccion = "Registrar inspección conforme",
    )
    DictamenCalidad.ADVERTENCIA_AGUA -> PresentacionDictamen(
        titular = "ADVERTENCIA POR AGUA (RN-05, primera vez)",
        tarifaResultante = "Descuento en la liquidación de esta semana",
        situacionPadron = "Permanece activo, con advertencia",
        etiquetaAccion = "Emitir advertencia y aplicar descuento",
    )
    DictamenCalidad.DESCUENTO_RETIRO_AGUA -> PresentacionDictamen(
        titular = "REINCIDENCIA POR AGUA (RN-05)",
        tarifaResultante = "Descuento en la liquidación",
        situacionPadron = "Retiro definitivo del padrón",
        etiquetaAccion = "Ejecutar descuento y retirar del padrón",
    )
    DictamenCalidad.EXPULSION_AGUA -> PresentacionDictamen(
        titular = "EXPULSIÓN POR AGUA (RN-05 ≥ 5%)",
        tarifaResultante = "Pago degradado a tarifa mínima",
        situacionPadron = "Expulsión inmediata del padrón",
        etiquetaAccion = "Ejecutar expulsión con tarifa mínima",
    )
    DictamenCalidad.RECHAZADO_ACIDEZ -> PresentacionDictamen(
        titular = "LOTE RECHAZADO POR ACIDEZ (RN-06)",
        tarifaResultante = "El lote no se paga; la próxima entrega sí, a tarifa normal",
        situacionPadron = "Permanece activo; derivación a capacitación",
        etiquetaAccion = "Derivar a Sanidad y Veterinaria (capacitación BPO)",
    )
}
