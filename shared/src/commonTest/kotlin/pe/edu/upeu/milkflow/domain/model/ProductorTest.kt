package pe.edu.upeu.milkflow.domain.model

import kotlin.test.Test
import kotlin.test.assertEquals

class ProductorTest {

    @Test
    fun nombreConCodigoCombinaAmbos() {
        val productor = Productor(
            id = "prod-01",
            nombre = "Juan Quispe",
            codigoSocio = "S-100",
            comunidad = "Huata"
        )

        assertEquals("Juan Quispe (S-100)", productor.nombreConCodigo)
    }
}
