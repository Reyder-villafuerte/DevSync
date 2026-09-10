package pe.edu.upeu.milkflow.di

import org.koin.core.module.Module
import org.koin.dsl.module
import pe.edu.upeu.milkflow.data.local.DriverFactory
import pe.edu.upeu.milkflow.data.local.DriverFactoryIos
import pe.edu.upeu.milkflow.domain.sync.ObservadorConectividad
import pe.edu.upeu.milkflow.infra.almacen.AlmacenSeguroToken
import pe.edu.upeu.milkflow.infra.almacen.AlmacenSeguroTokenIos
import pe.edu.upeu.milkflow.infra.conectividad.ObservadorConectividadIos

actual fun moduloPlataforma(): Module = module {
    single<DriverFactory> { DriverFactoryIos() }
    single<AlmacenSeguroToken> { AlmacenSeguroTokenIos() }
    single<ObservadorConectividad> { ObservadorConectividadIos() }
}
