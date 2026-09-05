package pe.edu.upeu.milkflow.data.local

import app.cash.sqldelight.driver.native.NativeSqliteDriver
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase

class IosDatabaseDriverFactory : DatabaseDriverFactory {
    override fun createDriver() = NativeSqliteDriver(
        schema = MilkFlowDatabase.Schema,
        name = "milkflow.db",
    )
}
