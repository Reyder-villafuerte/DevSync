package pe.edu.upeu.milkflow

import pe.edu.upeu.milkflow.domain.model.PrecioLecheVigente
import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertFailsWith
import kotlin.test.assertTrue

class PrecioLecheVigenteTest {

    private fun precioDemo(
        fechaInicio: String = "2026-08-01",
        fechaFin: String? = "2026-08-31"
    ) = PrecioLecheVigente(
        id = "prc-01",
        precioPorLitro = 3.5,
        fechaInicio = fechaInicio,
        fechaFin = fechaFin
    )

    @Test
    fun validaRangoDeVigenciaConLimitesIncluidos() {
        val precio = precioDemo()

        assertTrue(precio.estaVigenteEn("2026-08-01"))
        assertTrue(precio.estaVigenteEn("2026-08-20"))
        assertTrue(precio.estaVigenteEn("2026-08-31"))
        assertFalse(precio.estaVigenteEn("2026-09-01"))
    }

    @Test
    fun rechazaFechaFinAnteriorAFechaInicio() {
        assertFailsWith<IllegalArgumentException> {
            precioDemo(
                fechaInicio = "2026-09-01",
                fechaFin = "2026-08-31"
            )
        }
    }
}
