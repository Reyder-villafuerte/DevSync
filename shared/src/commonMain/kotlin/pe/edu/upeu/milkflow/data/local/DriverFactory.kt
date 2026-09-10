package pe.edu.upeu.milkflow.data.local

import app.cash.sqldelight.db.SqlDriver
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase

/**
 * Fábrica de driver SQLDelight por plataforma. La implementación concreta la
 * provee cada `actual`: AndroidSqliteDriver en Android, NativeSqliteDriver en iOS.
 */
interface DriverFactory {
    fun crear(): SqlDriver
}

fun crearBaseDatos(factory: DriverFactory): MilkFlowDatabase =
    MilkFlowDatabase(factory.crear())
