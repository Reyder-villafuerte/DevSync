package pe.edu.upeu.milkflow.di

import org.koin.core.module.Module
import org.koin.dsl.module
import pe.edu.upeu.milkflow.data.local.DatabaseDriverFactory

fun platformModule(driverFactory: DatabaseDriverFactory): Module = module {
    single<DatabaseDriverFactory> { driverFactory }
}
