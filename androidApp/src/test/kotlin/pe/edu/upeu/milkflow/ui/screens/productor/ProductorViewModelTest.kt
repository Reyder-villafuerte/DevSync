package pe.edu.upeu.milkflow.ui.screens.productor

import app.cash.turbine.test
import kotlin.time.Clock
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerAvisoActivoUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteAcopioUseCase
import pe.edu.upeu.milkflow.domain.usecase.SincronizarAhoraUseCase
import pe.edu.upeu.milkflow.domain.usecase.SolicitarCambioRutaUseCase
import pe.edu.upeu.milkflow.ui.FakeAvisoRepository
import pe.edu.upeu.milkflow.ui.FakeInspeccionRepository
import pe.edu.upeu.milkflow.ui.FakeLiquidacionRepository
import pe.edu.upeu.milkflow.ui.FakePrecioRepository
import pe.edu.upeu.milkflow.ui.FakeProductorRepository
import pe.edu.upeu.milkflow.ui.FakeRecoleccionRepository
import pe.edu.upeu.milkflow.ui.FakeRutaRepository
import pe.edu.upeu.milkflow.ui.FakeSancionRepository
import pe.edu.upeu.milkflow.ui.FakeSincronizacionRepository
import pe.edu.upeu.milkflow.ui.FakeSolicitudRutaRepository
import pe.edu.upeu.milkflow.ui.FakeZonaRepository
import pe.edu.upeu.milkflow.ui.IdSecuencial
import pe.edu.upeu.milkflow.ui.RelojFijo
import pe.edu.upeu.milkflow.ui.VmTestBase
import pe.edu.upeu.milkflow.ui.syncManagerDePrueba
import pe.edu.upeu.milkflow.ui.avisoObligatorio
import pe.edu.upeu.milkflow.ui.precioCompra
import pe.edu.upeu.milkflow.ui.productor
import pe.edu.upeu.milkflow.ui.recoleccion
import pe.edu.upeu.milkflow.ui.ruta
import pe.edu.upeu.milkflow.ui.sesion
import pe.edu.upeu.milkflow.ui.zona
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertNotNull
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class ProductorViewModelTest : VmTestBase() {

    private val avisos = FakeAvisoRepository(listOf(avisoObligatorio("av1", "Asamblea general")))
    private val recolecciones = FakeRecoleccionRepository()
    private val precios = FakePrecioRepository(listOf(precioCompra(1.70)))
    private val solicitudes = FakeSolicitudRutaRepository()
    private val productores = FakeProductorRepository(listOf(productor("p1", "z1")))

    private fun kotlinx.coroutines.test.TestScope.vm() = ProductorViewModel(
        sesion = sesion(RolUsuario.PRODUCTOR, Ambito.Productor("p1"), usuarioId = "u-p1"),
        obtenerAviso = ObtenerAvisoActivoUseCase(avisos, RelojFijo),
        avisos = avisos,
        productores = productores,
        zonas = FakeZonaRepository(listOf(zona("z1", "r1"), zona("z2", "r1"))),
        rutas = FakeRutaRepository(listOf(ruta("r1", "01"))),
        inspecciones = FakeInspeccionRepository(),
        obtenerReporte = ObtenerReporteAcopioUseCase(recolecciones),
        precios = precios,
        liquidaciones = FakeLiquidacionRepository(),
        sanciones = FakeSancionRepository(),
        solicitudes = solicitudes,
        solicitarCambio = SolicitarCambioRutaUseCase(productores, solicitudes, IdSecuencial, RelojFijo),
        sincronizarAhora = SincronizarAhoraUseCase(syncManagerDePrueba(backgroundScope, FakeSincronizacionRepository())),
        recolecciones = recolecciones,
    )

    @Test
    fun aviso_obligatorio_se_muestra_y_al_confirmar_desaparece() = runTest(dispatcher) {
        val vm = vm()
        vm.estado.test {
            var s = awaitItem()
            while (s.aviso == null) s = awaitItem()
            assertEquals("Asamblea general", s.aviso!!.titulo)

            vm.confirmarAviso()
            while (s.aviso != null) s = awaitItem()
            assertNull(s.aviso)
            cancelAndIgnoreRemainingEvents()
        }
        assertTrue(avisos.flujo.value.first().vistoLocalmente)
    }

    @Test
    fun pago_proyectado_es_litros_del_ciclo_por_tarifa() = runTest(dispatcher) {
        // Recolección de hoy -> cae en el ciclo jueves→miércoles vigente.
        recolecciones.flujo.value = listOf(recoleccion("r1", "j1", "p1", 40.0, Clock.System.now()))
        val vm = vm()
        vm.estado.test {
            var s = awaitItem()
            while (s.litrosCiclo == 0.0) s = awaitItem()
            assertEquals(1.70, s.tarifaLitro)
            assertEquals(s.litrosCiclo * 1.70, s.pagoProyectado, 0.001)
            assertTrue(s.litrosCiclo >= 40.0)
            cancelAndIgnoreRemainingEvents()
        }
    }

    @Test
    fun solicitud_cambio_ruta_se_registra() = runTest(dispatcher) {
        val vm = vm()
        vm.estado.test {
            var s = awaitItem()
            while (s.aviso == null) s = awaitItem()
            vm.confirmarAviso()
            vm.abrirSolicitud()
            while (s.sheet == null) s = awaitItem()
            vm.onZonaSolicitud("z2")
            vm.onMotivo("Vivo más cerca de la zona 2")
            awaitItem()
            vm.enviarSolicitud()
            while (s.sheet != null) s = awaitItem()
            cancelAndIgnoreRemainingEvents()
        }
        assertEquals(1, solicitudes.flujo.value.size)
        assertEquals("z2", solicitudes.flujo.value.first().zonaSolicitadaId)
    }
}
