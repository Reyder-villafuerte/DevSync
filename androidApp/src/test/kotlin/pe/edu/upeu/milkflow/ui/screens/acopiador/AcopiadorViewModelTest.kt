package pe.edu.upeu.milkflow.ui.screens.acopiador

import app.cash.turbine.test
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.usecase.IniciarJornadaUseCase
import pe.edu.upeu.milkflow.domain.usecase.RegistrarRecoleccionUseCase
import pe.edu.upeu.milkflow.ui.FakeJornadaRepository
import pe.edu.upeu.milkflow.ui.FakeProductorRepository
import pe.edu.upeu.milkflow.ui.FakeRecoleccionRepository
import pe.edu.upeu.milkflow.ui.FakeRutaRepository
import pe.edu.upeu.milkflow.ui.FakeZonaRepository
import pe.edu.upeu.milkflow.ui.IdSecuencial
import pe.edu.upeu.milkflow.ui.RelojFijo
import pe.edu.upeu.milkflow.ui.VmTestBase
import pe.edu.upeu.milkflow.ui.jornada
import pe.edu.upeu.milkflow.ui.productor
import pe.edu.upeu.milkflow.ui.ruta
import pe.edu.upeu.milkflow.ui.sesion
import pe.edu.upeu.milkflow.ui.zona
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class AcopiadorViewModelTest : VmTestBase() {

    private val jornadas = FakeJornadaRepository()
    private val recolecciones = FakeRecoleccionRepository()
    private val productores = FakeProductorRepository(
        listOf(
            productor("p1", "z1", nombres = "José", apellidos = "Ñato"),
            productor("p2", "z1", nombres = "Rosa", apellidos = "Vega"),
        ),
    )

    private fun vm(rutas: FakeRutaRepository = FakeRutaRepository(listOf(ruta("r1", "01")))) = AcopiadorViewModel(
        sesion = sesion(RolUsuario.ACOPIADOR, Ambito.Ruta("01"), usuarioId = "acop1"),
        rutas = rutas,
        zonas = FakeZonaRepository(listOf(zona("z1", "r1"))),
        productores = productores,
        jornadas = jornadas,
        recolecciones = recolecciones,
        iniciarJornada = IniciarJornadaUseCase(jornadas),
        registrarRecoleccion = RegistrarRecoleccionUseCase(jornadas, productores, recolecciones, IdSecuencial, RelojFijo),
    )

    @Test
    fun sin_jornada_luego_iniciar_y_registrar_sube_los_kpi() = runTest(dispatcher) {
        val vm = vm()
        vm.estado.test {
            var s = awaitItem()
            while (s.cargando) s = awaitItem()
            assertEquals(false, s.hayJornada)

            vm.iniciarRuta()
            while (!s.hayJornada) s = awaitItem()
            assertEquals(2, s.totalParadas)
            assertEquals(0.0, s.kpiLitros)

            // Registrar recolección para p1.
            val p1 = s.paradas.first { it.productorId == "p1" }
            vm.abrirSheet(p1)
            awaitItem()
            vm.onLitrosSheet("12.5")
            awaitItem()
            vm.confirmarRecoleccion()

            var conKpi = awaitItem()
            while (conKpi.kpiLitros == 0.0) conKpi = awaitItem()
            assertEquals(12.5, conKpi.kpiLitros)
            assertEquals(1, conKpi.kpiSocios)
            assertTrue(conKpi.paradas.first { it.productorId == "p1" }.completo)
            cancelAndIgnoreRemainingEvents()
        }
        assertEquals(1, recolecciones.flujo.value.size)
    }

    /** Sin catálogo bajado no hay ruta local: el botón no puede quedar "muerto". */
    @Test
    fun sin_ruta_en_la_bd_local_se_avisa_y_no_se_puede_iniciar() = runTest(dispatcher) {
        val vm = vm(rutas = FakeRutaRepository(emptyList()))
        vm.estado.test {
            var s = awaitItem()
            while (s.cargando) s = awaitItem()
            assertEquals(false, s.puedeIniciarRuta)
            assertEquals(0, s.totalParadas)
            assertTrue(s.avisoJornada!!.contains("Sincronizar"))

            vm.iniciarRuta()   // no debe abrir jornada ni reventar
            cancelAndIgnoreRemainingEvents()
        }
        assertEquals(null, jornadas.flujo.value)
    }

    @Test
    fun litros_invalidos_dejan_error_en_el_sheet_sin_tocar_kpi() = runTest(dispatcher) {
        jornadas.flujo.value = jornada("j1", "acop1", "r1")
        val vm = vm()
        vm.estado.test {
            var s = awaitItem()
            while (!s.hayJornada) s = awaitItem()
            vm.abrirSheet(s.paradas.first { it.productorId == "p1" })
            awaitItem()
            vm.onLitrosSheet("0")   // Litros.de rechaza <= 0
            awaitItem()
            vm.confirmarRecoleccion()
            var conError = awaitItem()
            while (conError.sheet?.error == null) conError = awaitItem()
            assertEquals(0.0, conError.kpiLitros)
            cancelAndIgnoreRemainingEvents()
        }
        assertEquals(0, recolecciones.flujo.value.size)
    }
}
