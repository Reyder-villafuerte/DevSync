package pe.edu.upeu.milkflow.data.local.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlin.time.Clock
import kotlinx.datetime.TimeZone
import kotlinx.datetime.todayIn
import pe.edu.upeu.milkflow.data.local.aDominio
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.domain.model.Liquidacion
import pe.edu.upeu.milkflow.domain.model.Precio
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.Ruta
import pe.edu.upeu.milkflow.domain.model.Sancion
import pe.edu.upeu.milkflow.domain.model.Zona
import pe.edu.upeu.milkflow.domain.repository.LiquidacionRepository
import pe.edu.upeu.milkflow.domain.repository.PrecioRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository

/*
 * Catálogos y agregados de solo lectura local. Se rellenan desde la bajada
 * (AplicadorCambios). Cada repositorio es su propia clase porque varias
 * interfaces comparten firmas (`porId`, `observarPorProductor`).
 */

private val LIMA = TimeZone.of("America/Lima")
private fun hoyIso() = Clock.System.todayIn(LIMA).toString()

class ProductorRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : ProductorRepository {
    private val q get() = db.milkFlowQueries
    override fun observarPorZona(zonaId: String) =
        q.productoresPorZona(zonaId).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }
    override fun observarPadron() =
        q.productoresPadron().asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }
    override suspend fun porId(id: String): Productor? = withContext(io) {
        q.productorPorId(id).executeAsOneOrNull()?.aDominio()
    }
}

class ZonaRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : ZonaRepository {
    private val q get() = db.milkFlowQueries
    override fun observarTodas() =
        q.zonasTodas().asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }
    override suspend fun porId(id: String): Zona? = withContext(io) {
        q.zonaPorId(id).executeAsOneOrNull()?.aDominio()
    }
}

class RutaRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : RutaRepository {
    private val q get() = db.milkFlowQueries
    override fun observarTodas() =
        q.rutasTodas().asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }
    override suspend fun porId(id: String): Ruta? = withContext(io) {
        q.rutaPorId(id).executeAsOneOrNull()?.aDominio()
    }
}

class SancionRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : SancionRepository {
    private val q get() = db.milkFlowQueries
    override fun observarPorProductor(productorId: String) =
        q.sancionesPorProductor(productorId).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }
    override suspend fun tieneSancionAguaVigente(productorId: String): Boolean = withContext(io) {
        q.tieneSancionAguaVigente(productorId).executeAsOne() > 0L
    }
}

class PrecioRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : PrecioRepository {
    private val q get() = db.milkFlowQueries
    override fun observarVigentes(): Flow<List<Precio>> =
        q.preciosVigentes(hoyIso()).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }
    override suspend fun tarifaCompraVigente(): Precio? = withContext(io) {
        q.tarifaCompraVigente(hoyIso()).executeAsOneOrNull()?.aDominio()
    }
}

class LiquidacionRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : LiquidacionRepository {
    private val q get() = db.milkFlowQueries
    override fun observarPorProductor(productorId: String): Flow<List<Liquidacion>> =
        q.liquidacionesPorProductor(productorId).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }
}
