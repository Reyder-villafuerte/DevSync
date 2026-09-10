package pe.edu.upeu.milkflow.di

import org.koin.android.ext.koin.androidContext
import org.koin.core.module.Module
import org.koin.dsl.module
import pe.edu.upeu.milkflow.data.local.DriverFactory
import pe.edu.upeu.milkflow.data.local.DriverFactoryAndroid
import pe.edu.upeu.milkflow.domain.sync.ObservadorConectividad
import pe.edu.upeu.milkflow.infra.almacen.AlmacenSeguroToken
import pe.edu.upeu.milkflow.infra.almacen.AlmacenSeguroTokenAndroid
import pe.edu.upeu.milkflow.infra.conectividad.ObservadorConectividadAndroid

actual fun moduloPlataforma(): Module = module {
    single<DriverFactory> { DriverFactoryAndroid(androidContext()) }
    single<AlmacenSeguroToken> { AlmacenSeguroTokenAndroid(androidContext()) }
    single<ObservadorConectividad> { ObservadorConectividadAndroid(androidContext()) }
}
