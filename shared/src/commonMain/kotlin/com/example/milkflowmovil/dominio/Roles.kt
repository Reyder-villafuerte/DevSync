package com.example.milkflowmovil.dominio

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
 * Los nueve roles del sistema y el menú que ve cada uno.
 * El menú replica la barra lateral del web: si un rol no tiene la pantalla
 * aquí, tampoco puede llegar a ella navegando.
 */
enum class Rol(val clave: String, val etiqueta: String) {
    PRODUCTOR("productor", "Productor / Proveedor"),
    ACOPIADOR("acopiador", "Acopiador"),
    JEFE_PRODUCCION("jefe_produccion", "Jefe de Producción"),
    INSPECTOR_CALIDAD("inspector_calidad", "Inspector de Calidad"),
    PERSONAL_VENTA("personal_venta", "Personal de Venta"),
    PERSONAL_PAGO("personal_pago", "Personal de Pago"),
    PAGADOR_CAMPO("pagador_campo", "Pagador de Campo"),
    ADMIN("admin", "Administración"),
    JEFE_GENERAL("jefe_general", "Jefe General"),
    DESCONOCIDO("", "Sin rol");

    val menu: List<Pantalla>
        get() = when (this) {
            PRODUCTOR -> listOf(
                Pantalla.MI_ACOPIO, Pantalla.MI_ZONA, Pantalla.MIS_DESCUENTOS,
                Pantalla.MIS_PAGOS, Pantalla.MI_CALIDAD, Pantalla.SINCRONIZACION,
            )

            ACOPIADOR -> listOf(
                Pantalla.ACOPIO, Pantalla.ACOPIO_HISTORIAL, Pantalla.SINCRONIZACION,
            )

            JEFE_PRODUCCION -> listOf(
                Pantalla.CAUDALIMETRO, Pantalla.SINCRONIZACION,
            )

            INSPECTOR_CALIDAD -> listOf(
                Pantalla.PANEL, Pantalla.CALIDAD, Pantalla.SINCRONIZACION,
            )

            PERSONAL_VENTA -> listOf(
                Pantalla.PANEL, Pantalla.VENTAS, Pantalla.RECIBOS, Pantalla.SINCRONIZACION,
            )

            PERSONAL_PAGO -> listOf(
                Pantalla.PANEL, Pantalla.AUTORIZAR_PAGOS, Pantalla.SOBRES_RUTA,
                Pantalla.SOBRES_HISTORIAL, Pantalla.SINCRONIZACION,
            )

            PAGADOR_CAMPO -> listOf(
                Pantalla.SOBRES_RUTA, Pantalla.SOBRES_HISTORIAL, Pantalla.SINCRONIZACION,
            )

            ADMIN -> listOf(
                Pantalla.PANEL, Pantalla.ACOPIO_HISTORIAL, Pantalla.ZONAS, Pantalla.SOLICITUDES_ZONA,
                Pantalla.CALIDAD, Pantalla.FINANZAS, Pantalla.RECIBOS, Pantalla.AUTORIZAR_PAGOS,
                Pantalla.TARIFAS, Pantalla.AVISOS, Pantalla.SINCRONIZACION,
            )

            JEFE_GENERAL -> listOf(
                Pantalla.PANEL, Pantalla.ACOPIO, Pantalla.ACOPIO_HISTORIAL, Pantalla.CAUDALIMETRO,
                Pantalla.VENTAS, Pantalla.RECIBOS, Pantalla.CALIDAD,
                Pantalla.ZONAS, Pantalla.SOLICITUDES_ZONA, Pantalla.FINANZAS,
                Pantalla.AUTORIZAR_PAGOS, Pantalla.SOBRES_RUTA, Pantalla.TARIFAS,
                Pantalla.AVISOS, Pantalla.SINCRONIZACION,
            )

            DESCONOCIDO -> listOf(Pantalla.SINCRONIZACION)
        }

    /** Pantalla de arranque: el web también redirige a cada rol a su trabajo. */
    val inicio: Pantalla get() = menu.first()

    fun puedeVer(pantalla: Pantalla): Boolean = when (pantalla) {
        Pantalla.NUEVA_VENTA -> menu.contains(Pantalla.VENTAS)
        Pantalla.RECIBO_DETALLE -> menu.contains(Pantalla.RECIBOS) || this == PRODUCTOR
        else -> menu.contains(pantalla)
    }

    companion object {
        fun desde(clave: String?) = entries.firstOrNull { it.clave == clave } ?: DESCONOCIDO
    }
}
