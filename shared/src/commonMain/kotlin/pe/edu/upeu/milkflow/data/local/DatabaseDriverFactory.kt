package pe.edu.upeu.milkflow.data.local

import app.cash.sqldelight.db.SqlDriver
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase

interface DatabaseDriverFactory {
    fun createDriver(): SqlDriver
}

fun createMilkFlowDatabase(factory: DatabaseDriverFactory): MilkFlowDatabase =
    MilkFlowDatabase(factory.createDriver())
