package pe.edu.upeu.milkflow.domain.model

import kotlin.test.Test
import kotlin.test.assertEquals

class UsuarioTest {

    @Test
    fun creaUsuarioCorrectamente() {
        val admin = Usuario(
            id = "u-01",
            username = "admin",
            rol = RolUsuario.ADMINISTRADOR
        )

        assertEquals("admin", admin.username)
        assertEquals(RolUsuario.ADMINISTRADOR, admin.rol)
    }
}
