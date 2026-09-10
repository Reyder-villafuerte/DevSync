package pe.edu.upeu.milkflow.ui.screens.conflictos

import app.cash.turbine.test
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.domain.sync.OperacionOutbox
import pe.edu.upeu.milkflow.domain.sync.TipoOperacion
import pe.edu.upeu.milkflow.domain.usecase.SincronizarAhoraUseCase
import pe.edu.upeu.milkflow.ui.FakeSincronizacionRepository
import pe.edu.upeu.milkflow.ui.T0
import pe.edu.upeu.milkflow.ui.VmTestBase
import pe.edu.upeu.milkflow.ui.components.EstadoUi
import pe.edu.upeu.milkflow.ui.syncManagerDePrueba
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

@OptIn(ExperimentalCoroutinesApi::class)
class ConflictosViewModelTest : VmTestBase() {

    @Test
    fun lista_vacia_y_luego_un_conflicto_visible() = runTest(dispatcher) {
        val sinc = FakeSincronizacionRepository()
        val vm = ConflictosViewModel(sinc, SincronizarAhoraUseCase(syncManagerDePrueba(backgroundScope, sinc)))

        vm.estado.test {
            assertEquals(EstadoUi.Cargando, awaitItem())
            assertTrue(awaitItem() is EstadoUi.Vacio)

            sinc.conflictos.value = listOf(
                OperacionOutbox(
                    idLocal = 7, operacion = TipoOperacion.INSERTAR, tabla = "registros_acopio",
                    idRegistro = "reg-123", payloadJson = "{}", versionBase = 0, intentos = 6,
                    ultimoError = "solo_insercion_no_actualizable", creadoEn = T0,
                ),
            )
            val contenido = awaitItem()
            assertTrue(contenido is EstadoUi.Contenido)
            val items = (contenido as EstadoUi.Contenido).datos
            assertEquals(1, items.size)
            assertEquals("registros_acopio", items.first().entidad)
            assertEquals("solo_insercion_no_actualizable", items.first().motivo)
        }
    }
}
