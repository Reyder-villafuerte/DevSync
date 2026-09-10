package pe.edu.upeu.milkflow.ui.navigation

import app.cash.turbine.test
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.ui.FakeSesionRepository
import pe.edu.upeu.milkflow.ui.VmTestBase
import pe.edu.upeu.milkflow.ui.sesion
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class SesionGateViewModelTest : VmTestBase() {

    @Test
    fun sin_sesion_luego_autenticado_acopiador() = runTest(dispatcher) {
        val repo = FakeSesionRepository()
        val vm = SesionGateViewModel(repo)

        vm.estado.test {
            assertEquals(EstadoSesion.Cargando, awaitItem())
            assertEquals(EstadoSesion.SinSesion, awaitItem())

            repo.sesion.value = sesion(RolUsuario.ACOPIADOR, Ambito.Ruta("01"))
            val autenticado = awaitItem()
            assertTrue(autenticado is EstadoSesion.Autenticado)
            assertEquals(RolUsuario.ACOPIADOR, (autenticado as EstadoSesion.Autenticado).sesion.rol)
        }
    }

    @Test
    fun rol_de_planta_no_soportado() = runTest(dispatcher) {
        val repo = FakeSesionRepository()
        repo.sesion.value = sesion(RolUsuario.ADMINISTRACION, Ambito.Global)
        val vm = SesionGateViewModel(repo)

        vm.estado.test {
            awaitItem() // Cargando
            val e = awaitItem()
            assertTrue(e is EstadoSesion.RolNoSoportado)
            assertEquals(RolUsuario.ADMINISTRACION, (e as EstadoSesion.RolNoSoportado).rol)
        }
    }
}
