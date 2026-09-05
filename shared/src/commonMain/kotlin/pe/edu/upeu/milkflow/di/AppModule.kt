package pe.edu.upeu.milkflow.di

import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import org.koin.core.KoinApplication
import org.koin.core.context.startKoin
import org.koin.core.module.Module
import org.koin.dsl.module
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository

typealias ValidadorClave = suspend (usuarioId: String, clave: String) -> Boolean

fun milkFlowModule(
    platformModule: Module,
    validarClave: ValidadorClave = { _, _ -> false },
): Module = module {
    includes(
        platformModule,
        persistenceModule(),
        repositoryModule(validarClave),
        useCaseModule(),
        presentationModule(),
    )
}

fun initKoin(
    platformModule: Module,
    validarClave: ValidadorClave = { _, _ -> false },
    seedDatabase: Boolean = true,
): KoinApplication = startKoin {
    allowOverride(false)
    modules(milkFlowModule(platformModule, validarClave))
}.also {
    if (seedDatabase) {
        CoroutineScope(Dispatchers.Default).launch {
            try {
                val repo = it.koin.get<UsuarioRepository>()
                if (repo.obtenerPorId("admin-id") == null) {
                    repo.guardar(
                        Usuario(
                            id = "admin-id",
                            nombreUsuario = "admin",
                            nombre = "Administradora MilkFlow",
                            rol = RolUsuario.ADMINISTRADORA,
                            activo = true,
                        ),
                        "admin123"
                    )
                }
            } catch (_: Exception) {
                // Ignorar fallos en el sembrado (p.ej. si el driver se cierra en tests)
            }
        }
    }
}
