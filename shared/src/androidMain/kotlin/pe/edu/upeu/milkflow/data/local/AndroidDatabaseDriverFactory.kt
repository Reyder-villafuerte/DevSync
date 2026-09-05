package pe.edu.upeu.milkflow.data.local

import android.content.Context
import app.cash.sqldelight.driver.android.AndroidSqliteDriver
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase

class AndroidDatabaseDriverFactory(
    private val context: Context,
) : DatabaseDriverFactory {
    override fun createDriver() = AndroidSqliteDriver(
        schema = MilkFlowDatabase.Schema,
        context = context,
        name = "milkflow.db",
    )
}
