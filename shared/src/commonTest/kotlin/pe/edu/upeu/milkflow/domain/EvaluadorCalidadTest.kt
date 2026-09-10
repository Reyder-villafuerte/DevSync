package pe.edu.upeu.milkflow.domain

import pe.edu.upeu.milkflow.domain.calidad.EvaluadorCalidad
import pe.edu.upeu.milkflow.domain.model.DictamenCalidad
import pe.edu.upeu.milkflow.domain.vo.MedicionLactoscan
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * RN-05 (agua) y RN-06 (acidez) con los casos límite del enunciado:
 * 4.9 %, 5.0 %, pH 6.49 y pH 6.5.
 */
class EvaluadorCalidadTest {

    private val evaluador = EvaluadorCalidad()

    private fun agua(pct: Double) = MedicionLactoscan(aguaAnadidaPorcentaje = pct, ph = null)
    private fun ph(v: Double) = MedicionLactoscan(aguaAnadidaPorcentaje = 0.0, ph = v)

    // ---- RN-05 ----

    @Test
    fun agua_4_9_primera_vez_es_advertencia() {
        val r = evaluador.evaluar(agua(4.9), tieneSancionAguaPrevia = false)
        assertEquals(DictamenCalidad.ADVERTENCIA_AGUA, r.dictamen)
        assertFalse(r.rechazaLote)
    }

    @Test
    fun agua_4_9_reincidente_es_descuento_y_retiro() {
        val r = evaluador.evaluar(agua(4.9), tieneSancionAguaPrevia = true)
        assertEquals(DictamenCalidad.DESCUENTO_RETIRO_AGUA, r.dictamen)
        assertTrue(r.esReincidencia)
    }

    @Test
    fun agua_5_0_exacto_es_expulsion() {
        val r = evaluador.evaluar(agua(5.0), tieneSancionAguaPrevia = false)
        assertEquals(DictamenCalidad.EXPULSION_AGUA, r.dictamen)
        assertTrue(r.rechazaLote)
    }

    @Test
    fun agua_5_0_expulsa_incluso_sin_antecedentes() {
        val r = evaluador.evaluar(agua(5.001), tieneSancionAguaPrevia = false)
        assertEquals(DictamenCalidad.EXPULSION_AGUA, r.dictamen)
    }

    // ---- RN-06 ----

    @Test
    fun ph_6_49_rechaza_el_lote_sin_expulsion() {
        val r = evaluador.evaluar(ph(6.49), tieneSancionAguaPrevia = false)
        assertEquals(DictamenCalidad.RECHAZADO_ACIDEZ, r.dictamen)
        assertTrue(r.rechazaLote)
    }

    @Test
    fun ph_6_5_exacto_esta_aprobado() {
        val r = evaluador.evaluar(ph(6.5), tieneSancionAguaPrevia = false)
        assertEquals(DictamenCalidad.APROBADO, r.dictamen)
        assertFalse(r.rechazaLote)
    }

    // ---- combinación: agua >= 5% domina sobre pH bajo ----

    @Test
    fun agua_alta_y_ph_bajo_devuelve_expulsion() {
        val r = evaluador.evaluar(MedicionLactoscan(aguaAnadidaPorcentaje = 6.0, ph = 6.2), tieneSancionAguaPrevia = false)
        assertEquals(DictamenCalidad.EXPULSION_AGUA, r.dictamen)
    }

    @Test
    fun sin_agua_y_ph_ok_esta_aprobado() {
        val r = evaluador.evaluar(MedicionLactoscan(aguaAnadidaPorcentaje = 0.0, ph = 6.8), tieneSancionAguaPrevia = false)
        assertEquals(DictamenCalidad.APROBADO, r.dictamen)
    }
}
