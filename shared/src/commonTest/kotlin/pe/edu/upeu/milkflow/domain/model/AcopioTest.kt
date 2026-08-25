package pe.edu.upeu.milkflow.domain.model

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

class AcopioTest {

    @Test
    fun calculaTotalPagoCorrectamente() {
        val acopio = Acopio(
            id = "1",
            fecha = 123456789L,
            productorId = "P001",
            litros = 10.5,
            precioAplicado = 2.0,
            estadoSinc = EstadoSincronizacion.PENDIENTE
        )

        assertEquals(21.0, acopio.totalPago, "El total de pago debe ser litros * precio")
    }

    @Test
    fun validaLitrosPositivos() {
        val acopioValido = Acopio(
            id = "1",
            fecha = 123456789L,
            productorId = "P001",
            litros = 0.1,
            precioAplicado = 1.0
        )
        val acopioInvalido = acopioValido.copy(litros = 0.0)

        assertTrue(acopioValido.esValido, "Acopio con litros > 0 debe ser válido")
        assertFalse(acopioInvalido.esValido, "Acopio con litros <= 0 debe ser inválido")
    }
}
