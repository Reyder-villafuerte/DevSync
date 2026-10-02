package com.example.milkflowmovil.presentation.navigation

import com.example.milkflowmovil.domain.model.Rol

/** Pantallas de la app. Son las mismas del panel web, adaptadas al teléfono. */
enum class Pantalla(val titulo: String, val icono: String) {
    PANEL("Panel del día", "▦"),

    ACOPIO("Acopio 4:30 AM", "🥛"),
    ACOPIO_HISTORIAL("Historial y reportes", "🧾"),

    CAUDALIMETRO("Caudalímetro", "📏"),

    VENTAS("Ventas del día", "🛒"),
    NUEVA_VENTA("Nueva venta", "➕"),
    RECIBOS("Recibos", "📄"),
    RECIBO_DETALLE("Recibo", "📄"),

    CALIDAD("Lactoscan y citas", "🔬"),

    ZONAS("Zonas (1 a 4)", "🗺️"),
    SOLICITUDES_ZONA("Solicitudes de zona", "📬"),

    AUTORIZAR_PAGOS("Autorizar pagos", "✅"),
    SOBRES_RUTA("Sobres en ruta", "💵"),
    SOBRES_HISTORIAL("Historial de sobres", "📚"),

    TARIFAS("Tarifas y precios", "🏷️"),
    AVISOS("Avisos de inicio", "📢"),
    FINANZAS("Flujo de caja", "📊"),

    MI_ACOPIO("Mi acopio", "🥛"),
    MI_ZONA("Cambio de zona", "🗺️"),
    MIS_DESCUENTOS("Descuentos", "➖"),
    MIS_PAGOS("Historial de pagos", "💰"),
    MI_CALIDAD("Calidad de mi leche", "🔬"),

    SINCRONIZACION("Sincronización", "🔄"),
}

/**
 * Menú que ve cada rol. Replica la barra lateral del web: si un rol no tiene
 * la pantalla aquí, tampoco puede llegar a ella navegando.
 */
val Rol.menu: List<Pantalla>
    get() = when (this) {
        Rol.PRODUCTOR -> listOf(
            Pantalla.MI_ACOPIO, Pantalla.MI_ZONA, Pantalla.MIS_DESCUENTOS,
            Pantalla.MIS_PAGOS, Pantalla.MI_CALIDAD, Pantalla.SINCRONIZACION,
        )

        Rol.ACOPIADOR -> listOf(
            Pantalla.ACOPIO, Pantalla.ACOPIO_HISTORIAL, Pantalla.SINCRONIZACION,
        )

        Rol.JEFE_PRODUCCION -> listOf(
            Pantalla.CAUDALIMETRO, Pantalla.SINCRONIZACION,
        )

        Rol.INSPECTOR_CALIDAD -> listOf(
            Pantalla.PANEL, Pantalla.CALIDAD, Pantalla.SINCRONIZACION,
        )

        Rol.PERSONAL_VENTA -> listOf(
            Pantalla.PANEL, Pantalla.VENTAS, Pantalla.RECIBOS, Pantalla.SINCRONIZACION,
        )

        Rol.PERSONAL_PAGO -> listOf(
            Pantalla.PANEL, Pantalla.AUTORIZAR_PAGOS, Pantalla.SOBRES_RUTA,
            Pantalla.SOBRES_HISTORIAL, Pantalla.SINCRONIZACION,
        )

        Rol.PAGADOR_CAMPO -> listOf(
            Pantalla.SOBRES_RUTA, Pantalla.SOBRES_HISTORIAL, Pantalla.SINCRONIZACION,
        )

        Rol.ADMIN -> listOf(
            Pantalla.PANEL, Pantalla.ACOPIO_HISTORIAL, Pantalla.ZONAS, Pantalla.SOLICITUDES_ZONA,
            Pantalla.CALIDAD, Pantalla.FINANZAS, Pantalla.RECIBOS, Pantalla.AUTORIZAR_PAGOS,
            Pantalla.TARIFAS, Pantalla.AVISOS, Pantalla.SINCRONIZACION,
        )

        Rol.JEFE_GENERAL -> listOf(
            Pantalla.PANEL, Pantalla.ACOPIO, Pantalla.ACOPIO_HISTORIAL, Pantalla.CAUDALIMETRO,
            Pantalla.VENTAS, Pantalla.RECIBOS, Pantalla.CALIDAD,
            Pantalla.ZONAS, Pantalla.SOLICITUDES_ZONA, Pantalla.FINANZAS,
            Pantalla.AUTORIZAR_PAGOS, Pantalla.SOBRES_RUTA, Pantalla.TARIFAS,
            Pantalla.AVISOS, Pantalla.SINCRONIZACION,
        )

        Rol.DESCONOCIDO -> listOf(Pantalla.SINCRONIZACION)
    }

/** Pantalla de arranque: el web también redirige a cada rol a su trabajo. */
val Rol.inicio: Pantalla get() = menu.first()

fun Rol.puedeVer(pantalla: Pantalla): Boolean = when (pantalla) {
    Pantalla.NUEVA_VENTA -> menu.contains(Pantalla.VENTAS)
    Pantalla.RECIBO_DETALLE -> menu.contains(Pantalla.RECIBOS) || this == Rol.PRODUCTOR
    else -> menu.contains(pantalla)
}
