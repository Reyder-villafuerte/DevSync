package pe.edu.upeu.milkflow.di

import org.koin.core.Koin
import org.koin.core.KoinApplication
import org.koin.core.context.startKoin
import org.koin.dsl.KoinAppDeclaration

/**
 * Punto de entrada del módulo shared. Lo llama:
 *  - Android: MilkFlowApplication.onCreate()
 *  - iOS: iOSApp.init() vía `MilkFlowSharedKt.iniciarKoin(...)`
 */
fun iniciarKoin(
    config: ConfiguracionMilkFlow,
    extra: KoinAppDeclaration? = null,
): KoinApplication = startKoin {
    modules(moduloCompartido(config), moduloPlataforma())
    extra?.invoke(this)
}

/** Helper para iOS (sin default params en Swift). */
fun iniciarKoinIos(urlBase: String): Koin =
    iniciarKoin(ConfiguracionMilkFlow(urlBase)).koin
