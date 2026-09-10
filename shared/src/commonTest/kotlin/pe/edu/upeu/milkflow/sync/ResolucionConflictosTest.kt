package pe.edu.upeu.milkflow.sync

import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.sync.OperacionAceptada
import pe.edu.upeu.milkflow.domain.sync.OperacionEnConflicto
import pe.edu.upeu.milkflow.domain.sync.OperacionRechazada
import pe.edu.upeu.milkflow.domain.sync.PoliticaReintento
import pe.edu.upeu.milkflow.domain.sync.ResultadoSubida
import pe.edu.upeu.milkflow.domain.sync.SyncManager
import pe.edu.upeu.milkflow.domain.sync.TipoOperacion
import pe.edu.upeu.milkflow.fakes.ClienteSincronizacionFake
import pe.edu.upeu.milkflow.fakes.ConectividadFake
import pe.edu.upeu.milkflow.fakes.RelojFijo
import pe.edu.upeu.milkflow.fakes.SesionRepositoryFake
import pe.edu.upeu.milkflow.fakes.SincronizacionRepositoryFake
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

/**
 * Resolución de conflictos por dominio en la SUBIDA:
 *  - solo_insercion / version  -> CONFLICTO visible en la UI
 *  - conmutativo / servidor_gana -> se descarta (el servidor manda)
 *  - reintentos agotados        -> CONFLICTO (no reintento infinito)
 */
class ResolucionConflictosTest {

    private fun manager(
        repo: SincronizacionRepositoryFake,
        cliente: ClienteSincronizacionFake,
        maxIntentos: Int = 3,
    ): SyncManager {
        val scope = kotlinx.coroutines.CoroutineScope(kotlinx.coroutines.Dispatchers.Unconfined)
        return SyncManager(
            sincronizacion = repo,
            cliente = cliente,
            sesion = SesionRepositoryFake(),
            conectividad = ConectividadFake(inicial = false),
            politica = PoliticaReintento(maxIntentos = maxIntentos),
            reloj = RelojFijo(),
            alcance = scope,
        )
    }

    @Test
    fun conflicto_de_version_queda_marcado_como_conflicto() = runTest {
        val repo = SincronizacionRepositoryFake()
        repo.encolar(TipoOperacion.ACTUALIZAR, "rutas_acopio", "j-1", "{}", 0)
        val cliente = ClienteSincronizacionFake().apply {
            respuestaSubida = {
                Resultado.Exito(ResultadoSubida(
                    aceptadas = emptyList(),
                    conflictos = listOf(OperacionEnConflicto("j-1", "rutas_acopio", "version_desactualizada", """{"estado":"en_curso"}""")),
                    rechazadas = emptyList(),
                ))
            }
        }

        manager(repo, cliente).sincronizar()

        assertEquals(1, repo.filas.size)
        assertEquals("CONFLICTO", repo.filas.first().ultimoError)
    }

    @Test
    fun conflicto_conmutativo_se_descarta() = runTest {
        val repo = SincronizacionRepositoryFake()
        repo.encolar(TipoOperacion.INSERTAR, "movimientos_stock", "m-1", "{}", 0)
        val cliente = ClienteSincronizacionFake().apply {
            respuestaSubida = {
                Resultado.Exito(ResultadoSubida(
                    aceptadas = emptyList(),
                    conflictos = listOf(OperacionEnConflicto("m-1", "movimientos_stock", "ya_existe", null)),
                    rechazadas = emptyList(),
                ))
            }
        }

        manager(repo, cliente).sincronizar()

        assertTrue(repo.filas.isEmpty()) // el servidor ya lo tiene: nada que hacer
    }

    @Test
    fun operacion_aceptada_sale_del_outbox_y_guarda_cursor_al_bajar() = runTest {
        val repo = SincronizacionRepositoryFake()
        repo.encolar(TipoOperacion.INSERTAR, "registros_acopio", "r-1", "{}", 0)
        val cliente = ClienteSincronizacionFake().apply {
            respuestaSubida = {
                Resultado.Exito(ResultadoSubida(listOf(OperacionAceptada("r-1", "registros_acopio", 1, "insertado")), emptyList(), emptyList()))
            }
        }

        val r = manager(repo, cliente).sincronizar()

        assertTrue(r is Resultado.Exito)
        assertTrue(repo.filas.isEmpty())
        assertEquals("cursor-1", repo.cursores["ruta:01"])
        assertTrue(repo.ultimoExitoIso != null)
    }

    @Test
    fun rechazo_de_validacion_se_marca_como_conflicto() = runTest {
        val repo = SincronizacionRepositoryFake()
        repo.encolar(TipoOperacion.INSERTAR, "registros_acopio", "r-1", "{}", 0)
        val cliente = ClienteSincronizacionFake().apply {
            respuestaSubida = {
                Resultado.Exito(ResultadoSubida(emptyList(), emptyList(),
                    listOf(OperacionRechazada("r-1", "registros_acopio", "violacion_integridad"))))
            }
        }

        manager(repo, cliente).sincronizar()

        assertEquals("CONFLICTO", repo.filas.first().ultimoError)
    }
}
