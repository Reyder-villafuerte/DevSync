package pe.edu.upeu.milkflow.data

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.test.runTest
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.local.repository.RecoleccionRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.SincronizacionRepositoryLocal
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.vo.Litros
import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue
import kotlin.time.Instant

/**
 * Verifica el ESQUEMA REAL de MilkFlow.sq con un driver SQLite en memoria
 * (JdbcSqliteDriver), no con un fake. Cubre:
 *  - el `ON CONFLICT(tabla, id_registro) DO UPDATE` del outbox (idempotencia);
 *  - que la escritura de negocio y su encolado ocurran en la MISMA transacción
 *    (una violación de índice único no deja estado a medias).
 */
class EsquemaMilkFlowTest {

    private lateinit var driver: SqlDriver
    private lateinit var db: MilkFlowDatabase
    private val io = Dispatchers.Unconfined
    private val reloj = Reloj { Instant.parse("2026-09-09T10:00:00Z") }
    private val ids = object : GeneradorId {
        var n = 0
        override fun nuevo() = "id-${++n}"
    }

    @BeforeTest
    fun preparar() {
        driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
        MilkFlowDatabase.Schema.create(driver)
        db = MilkFlowDatabase(driver)
    }

    @AfterTest
    fun cerrar() {
        driver.close()
    }

    private fun recoleccion(id: String, jornada: String, productor: String, litros: Double) = Recoleccion(
        id = id, jornadaId = jornada, productorId = productor,
        litros = Litros.confiar(litros), horaRegistro = reloj.ahora(),
        observacion = null, sospechaAdulteracion = false,
        updatedAt = reloj.ahora(), version = 0, deleted = false,
    )

    @Test
    fun outbox_on_conflict_deja_una_sola_operacion_por_registro() = runTest {
        val q = db.milkFlowQueries
        q.encolar("INSERTAR", "registros_acopio", "r-1", """{"litros":10}""", 0, reloj.ahora().toEpochMilliseconds())
        q.encolar("INSERTAR", "registros_acopio", "r-1", """{"litros":12}""", 0, reloj.ahora().toEpochMilliseconds())

        assertEquals(1L, q.contarPendientes().executeAsOne())
        assertEquals("""{"litros":12}""", q.lotePendiente(10).executeAsOne().payload_json)
    }

    @Test
    fun registrar_escribe_negocio_y_outbox_en_la_misma_transaccion() = runTest {
        val repo = RecoleccionRepositoryLocal(db, io)
        val r = repo.registrar(recoleccion(ids.nuevo(), "j-1", "p-1", 12.5))

        assertIs<Resultado.Exito<*>>(r)
        assertEquals(1, db.milkFlowQueries.recoleccionesTodas().executeAsList().size)
        assertEquals(1L, db.milkFlowQueries.contarPendientes().executeAsOne())
    }

    @Test
    fun violacion_de_indice_unico_no_deja_estado_a_medias() = runTest {
        val repo = RecoleccionRepositoryLocal(db, io)
        assertIs<Resultado.Exito<*>>(repo.registrar(recoleccion("a", "j-1", "p-1", 10.0)))

        // Segunda recolección del mismo productor en la misma jornada:
        // choca contra recolecciones_jornada_productor (UNIQUE).
        val r2 = repo.registrar(recoleccion("b", "j-1", "p-1", 9.0))

        assertIs<Resultado.Fallo>(r2)
        // ni la fila de negocio "b" ni una segunda operación de outbox entraron.
        assertEquals(1, db.milkFlowQueries.recoleccionesTodas().executeAsList().size)
        assertEquals(1L, db.milkFlowQueries.contarPendientes().executeAsOne())
    }

    @Test
    fun cursor_y_estado_persisten_por_ambito() = runTest {
        val sync = SincronizacionRepositoryLocal(db, io)
        sync.guardarCursor(pe.edu.upeu.milkflow.domain.model.Ambito.Ruta("01"), "2026-09-09T10:05:00Z")

        assertEquals("2026-09-09T10:05:00Z", sync.cursor(pe.edu.upeu.milkflow.domain.model.Ambito.Ruta("01")))
        assertTrue(sync.cursor(pe.edu.upeu.milkflow.domain.model.Ambito.Ruta("02")) == null)
    }
}
