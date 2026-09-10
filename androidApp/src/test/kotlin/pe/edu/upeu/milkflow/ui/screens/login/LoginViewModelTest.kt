package pe.edu.upeu.milkflow.ui.screens.login

import app.cash.turbine.test
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.usecase.IniciarSesionUseCase
import pe.edu.upeu.milkflow.ui.FakeSesionRepository
import pe.edu.upeu.milkflow.ui.VmTestBase
import pe.edu.upeu.milkflow.ui.sesion
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest : VmTestBase() {

    private fun vmCon(resultado: Resultado<pe.edu.upeu.milkflow.domain.repository.SesionActiva>): Pair<LoginViewModel, FakeSesionRepository> {
        val repo = FakeSesionRepository(resultadoLogin = resultado)
        val vm = LoginViewModel(IniciarSesionUseCase(repo)) { "disp-test" }
        vm.onDni("70000001")
        vm.onPassword("secreto")
        return vm to repo
    }

    @Test
    fun sin_red_muestra_mensaje_de_primer_inicio() = runTest(dispatcher) {
        val (vm, _) = vmCon(Resultado.Fallo(ErrorApp.SinRed()))
        vm.estado.test {
            assertEquals(false, awaitItem().cargando)          // estado inicial (con dni/pass ya seteados)
            vm.ingresar()
            assertTrue(awaitItem().cargando)                    // -> cargando
            val fin = awaitItem()
            assertEquals(false, fin.cargando)
            assertTrue(fin.error!!.contains("primer inicio de sesión"))
        }
    }

    @Test
    fun no_autorizado_muestra_credenciales_invalidas() = runTest(dispatcher) {
        val (vm, _) = vmCon(Resultado.Fallo(ErrorApp.NoAutorizado))
        vm.estado.test {
            awaitItem()
            vm.ingresar()
            awaitItem() // cargando
            assertEquals("DNI o contraseña incorrectos.", awaitItem().error)
        }
    }

    @Test
    fun credenciales_ok_terminan_sin_error_y_publican_sesion() = runTest(dispatcher) {
        val ok = Resultado.Exito(sesion(RolUsuario.ACOPIADOR, Ambito.Ruta("01")))
        val (vm, repo) = vmCon(ok)
        vm.estado.test {
            awaitItem()
            vm.ingresar()
            awaitItem() // cargando
            val fin = awaitItem()
            assertEquals(false, fin.cargando)
            assertNull(fin.error)
        }
        assertEquals(RolUsuario.ACOPIADOR, repo.sesion.value?.rol)
    }
}
