package pe.edu.upeu.milkflow.sync

import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.domain.sync.ResultadoSubida
import pe.edu.upeu.milkflow.domain.sync.TipoOperacion
import pe.edu.upeu.milkflow.fakes.SincronizacionRepositoryFake
import pe.edu.upeu.milkflow.domain.sync.OperacionAceptada
import pe.edu.upeu.milkflow.core.Resultado
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

/**
 * Idempotencia del outbox:
 *  - encolar dos veces la misma (tabla, id_registro) deja UNA sola operación
 *    (replica el ON CONFLICT DO UPDATE del esquema).
 *  - una operación aceptada por el servidor sale del outbox; reenviar el lote
 *    no vuelve a crear nada.
 */
class OutboxIdempotenciaTest {

    @Test
    fun encolar_dos_veces_el_mismo_registro_no_duplica() = runTest {
        val repo = SincronizacionRepositoryFake()

        repo.encolar(TipoOperacion.INSERTAR, "registros_acopio", "r-1", """{"litros":10}""", 0)
        repo.encolar(TipoOperacion.INSERTAR, "registros_acopio", "r-1", """{"litros":12}""", 0)

        assertEquals(1, repo.filas.size)
        assertEquals("""{"litros":12}""", repo.filas.first().payloadJson) // se quedó el último payload
    }

    @Test
    fun operacion_aceptada_sale_del_outbox_y_no_se_reenvia() = runTest {
        val repo = SincronizacionRepositoryFake()
        repo.encolar(TipoOperacion.INSERTAR, "registros_acopio", "r-1", "{}", 0)
        val idLocal = repo.filas.first().idLocal

        // El servidor acepta la operación.
        repo.marcarSincronizada(idLocal, versionServidor = 5)

        assertTrue(repo.filas.isEmpty())
        assertEquals(emptyList(), repo.siguienteLote(50))
    }

    @Test
    fun operaciones_distintas_conviven() = runTest {
        val repo = SincronizacionRepositoryFake()
        repo.encolar(TipoOperacion.INSERTAR, "registros_acopio", "r-1", "{}", 0)
        repo.encolar(TipoOperacion.INSERTAR, "registros_acopio", "r-2", "{}", 0)
        repo.encolar(TipoOperacion.ACTUALIZAR, "rutas_acopio", "j-1", "{}", 2)

        assertEquals(3, repo.siguienteLote(50).size)
    }
}
