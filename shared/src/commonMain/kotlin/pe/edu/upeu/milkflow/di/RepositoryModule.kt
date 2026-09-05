package pe.edu.upeu.milkflow.di

import app.cash.sqldelight.db.SqlDriver
import org.koin.core.module.Module
import org.koin.dsl.module
import org.koin.dsl.onClose
import pe.edu.upeu.milkflow.data.local.DatabaseDriverFactory
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.repository.SqlDelightAcopiadorRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightAuditoriaRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightCalidadRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightEntregaRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightProductorRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightSincronizacionRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightUsuarioRepository
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository

internal fun persistenceModule(): Module = module {
    single<SqlDriver> {
        get<DatabaseDriverFactory>().createDriver()
    } onClose { driver ->
        driver?.close()
    }
    single { MilkFlowDatabase(get()) }
}

internal fun repositoryModule(validarClave: ValidadorClave): Module = module {
    single<UsuarioRepository> { SqlDelightUsuarioRepository(get(), validarClave) }
    single<ProductorRepository> { SqlDelightProductorRepository(get()) }
    single<AcopiadorRepository> { SqlDelightAcopiadorRepository(get()) }
    single<EntregaRepository> { SqlDelightEntregaRepository(get()) }
    single<CalidadRepository> { SqlDelightCalidadRepository(get()) }
    single<AuditoriaRepository> { SqlDelightAuditoriaRepository(get()) }
    single<SincronizacionRepository> { SqlDelightSincronizacionRepository(get()) }
}
