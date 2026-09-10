package pe.edu.upeu.milkflow.ui.navigation

/**
 * Rutas de navegación. Se agrupan por rol; el grafo que se registra depende del
 * rol autenticado, así que un destino de otro rol no existe en el `NavController`.
 */
object Destinos {
    const val LOGIN = "login"
    const val CONFLICTOS = "conflictos"
    const val ROL_NO_SOPORTADO = "rol_no_soportado"

    // Acopiador
    const val ACOPIADOR_HOME = "acopiador/home"
    const val ACOPIADOR_CIERRE = "acopiador/cierre"
    const val ACOPIADOR_COMPROBANTE = "acopiador/comprobante"

    // Supervisor
    const val SUPERVISOR_INSPECCION = "supervisor/inspeccion"
    const val SUPERVISOR_HISTORIAL = "supervisor/historial"

    // Productor
    const val PRODUCTOR_DASHBOARD = "productor/dashboard"
}
