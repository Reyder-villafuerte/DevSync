package pe.edu.upeu.milkflow.data.local

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.native.NativeSqliteDriver
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase

class DriverFactoryIos : DriverFactory {
    override fun crear(): SqlDriver = NativeSqliteDriver(
        schema = MilkFlowDatabase.Schema,
        name = "milkflow.db",
    )
}
