package com.example.milkflowmovil.domain.model

/**
 * Los nueve roles del sistema.
 *
 * El dominio solo sabe qué roles existen. Qué pantallas ve cada uno es asunto
 * de la presentación: está en `presentation/navigation/Pantalla.kt`.
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

    companion object {
        fun desde(clave: String?) = entries.firstOrNull { it.clave == clave } ?: DESCONOCIDO
    }
}
