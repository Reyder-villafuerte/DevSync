package pe.edu.upeu.milkflow.ui.screens.supervisor

import app.cash.turbine.test
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.domain.calidad.EvaluadorCalidad
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.DictamenCalidad
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.usecase.EvaluarCalidadUseCase
import pe.edu.upeu.milkflow.ui.FakeInspeccionRepository
import pe.edu.upeu.milkflow.ui.FakeProductorRepository
import pe.edu.upeu.milkflow.ui.FakeRutaRepository
import pe.edu.upeu.milkflow.ui.FakeSancionRepository
import pe.edu.upeu.milkflow.ui.FakeZonaRepository
import pe.edu.upeu.milkflow.ui.IdSecuencial
import pe.edu.upeu.milkflow.ui.RelojFijo
import pe.edu.upeu.milkflow.ui.VmTestBase
import pe.edu.upeu.milkflow.ui.productor
import pe.edu.upeu.milkflow.ui.ruta
import pe.edu.upeu.milkflow.ui.sesion
import pe.edu.upeu.milkflow.ui.zona
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class InspeccionViewModelTest : VmTestBase() {

    private val inspecciones = FakeInspeccionRepository()
    private val sanciones = FakeSancionRepository()
    private val productores = FakeProductorRepository(listOf(productor("p1", "z1", nombres = "Ana", apellidos = "Ruiz")))

    private fun vm(): InspeccionViewModel {
        val evaluador = EvaluadorCalidad()
        return InspeccionViewModel(
            sesion = sesion(RolUsuario.SUPERVISOR_CALIDAD, Ambito.Global, usuarioId = "sup1"),
            rutas = FakeRutaRepository(listOf(ruta("r1", "01"))),
            zonas = FakeZonaRepository(listOf(zona("z1", "r1"))),
            productores = productores,
            sanciones = sanciones,
            evaluador = evaluador,
            evaluarCalidad = EvaluarCalidadUseCase(productores, sanciones, inspecciones, evaluador, IdSecuencial, RelojFijo),
        )
    }

    @Test
    fun agua_6_previsualiza_expulsion_y_luego_ejecuta() = runTest(dispatcher) {
        val vm = vm()
        vm.estado.test {
            awaitItem()
            vm.onRuta("01")
            // Espera a que aparezca el productor de la ruta.
            var s = awaitItem()
            while (s.productores.isEmpty()) s = awaitItem()
            val p = s.productores.first()
            vm.onProductor(p)
            awaitItem()

            vm.onAgua("6")
            awaitItem()
            vm.previsualizar()
            var conPreview = awaitItem()
            while (conPreview.preview == null) conPreview = awaitItem()
            assertEquals(DictamenCalidad.EXPULSION_AGUA, conPreview.preview!!.dictamen)
            assertTrue(conPreview.preview!!.rechazaLote)

            vm.ejecutar()
            var registrada = awaitItem()
            while (!registrada.medidaRegistrada) registrada = awaitItem()
            assertNotNull(registrada.inspeccionParaImprimir)
            cancelAndIgnoreRemainingEvents()
        }
        assertEquals(1, inspecciones.flujo.value.size)
        assertEquals(DictamenCalidad.EXPULSION_AGUA, inspecciones.flujo.value.first().dictamen)
    }

    @Test
    fun sin_mediciones_marca_error_de_validacion() = runTest(dispatcher) {
        val vm = vm()
        vm.estado.test {
            awaitItem()
            vm.onRuta("01")
            var s = awaitItem()
            while (s.productores.isEmpty()) s = awaitItem()
            vm.onProductor(s.productores.first())
            awaitItem()
            vm.previsualizar()
            var conError = awaitItem()
            while (conError.errorForm == null) conError = awaitItem()
            assertTrue(conError.errorForm!!.contains("agua", ignoreCase = true) || conError.errorForm!!.contains("pH"))
            cancelAndIgnoreRemainingEvents()
        }
    }
}
