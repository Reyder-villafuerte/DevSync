package pe.edu.upeu.milkflow.presentation.navigation

sealed class AppDestination(
    val route: String,
    val title: String,
    val isBottomDestination: Boolean = false,
) {
    data object Login : AppDestination("login", "Acceso")
    data object Registro : AppDestination("registro", "Crear cuenta")
    data object Inicio : AppDestination("inicio", "Inicio", true)
    data object Entregas : AppDestination("entregas", "Entregas", true)
    data object RegistrarEntrega : AppDestination("entregas/registrar", "Registrar entrega")
    data object Productores : AppDestination("productores", "Productores")
    data object ActualizarProductor : AppDestination("productores/actualizar", "Actualizar productor")
    data object Acopiadores : AppDestination("acopiadores", "Acopiadores")
    data object RegistrarAcopiador : AppDestination("acopiadores/registrar", "Registrar acopiador")
    data object Calidad : AppDestination("calidad", "Control de calidad")
    data object RegistrarPruebaCalidad :
        AppDestination("calidad/registrar", "Registrar prueba de calidad")
    data object ConsultarEntregasProductor :
        AppDestination("consultas/entregas-productor", "Consultar entregas del productor")
    data object ResumenProductor :
        AppDestination("consultas/resumen-productor", "Resumen del productor")
    data object Reportes : AppDestination("reportes", "Reportes", true)
    data object Sincronizacion : AppDestination("sincronizacion", "Sincronización", true)
    data object Usuarios : AppDestination("usuarios", "Usuarios y permisos")
    data object Auditoria : AppDestination("auditoria", "Auditoría")
    data object Perfil : AppDestination("perfil", "Perfil", true)

    companion object {
        val all: List<AppDestination> by lazy {
            listOf(
                Login,
                Registro,
                Inicio,
                Entregas,
                RegistrarEntrega,
                Productores,
                ActualizarProductor,
                Acopiadores,
                RegistrarAcopiador,
                Calidad,
                RegistrarPruebaCalidad,
                ConsultarEntregasProductor,
                ResumenProductor,
                Reportes,
                Sincronizacion,
                Usuarios,
                Auditoria,
                Perfil,
            )
        }

        fun fromRoute(route: String?): AppDestination? {
            if (route == null) return null
            return all.firstOrNull { it.route == route }
        }
    }
}
