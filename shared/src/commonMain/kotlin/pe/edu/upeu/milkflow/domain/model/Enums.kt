package pe.edu.upeu.milkflow.domain.model

/** Roles del ecosistema. El `clave` coincide con el enum del backend. */
enum class RolUsuario(val clave: String) {
    ACOPIADOR("acopiador"),
    SUPERVISOR_CALIDAD("supervisor_calidad"),
    PRODUCTOR("productor"),
    JEFE_PRODUCCION("jefe_produccion"),
    DESPACHO_VENTAS("despacho_ventas"),
    ADMINISTRACION("administracion");

    companion object {
        fun desde(clave: String): RolUsuario? = entries.firstOrNull { it.clave == clave }
    }
}

enum class EstadoProductor(val clave: String) {
    ACTIVO("activo"),
    SUSPENDIDO("suspendido"),
    RETIRADO("retirado"),
    EXPULSADO("expulsado");

    val puedeEntregar: Boolean get() = this == ACTIVO || this == SUSPENDIDO

    companion object {
        fun desde(clave: String) = entries.firstOrNull { it.clave == clave } ?: ACTIVO
    }
}

/** Dictamen del control de calidad. Se calcula en cliente (EvaluarCalidadUseCase). */
enum class DictamenCalidad(val clave: String) {
    APROBADO("aprobado"),
    RECHAZADO_ACIDEZ("rechazado_acidez"),               // RN-06: pH < 6.5
    ADVERTENCIA_AGUA("advertencia_agua"),               // RN-05: < 5% primera vez
    DESCUENTO_RETIRO_AGUA("descuento_retiro_agua"),     // RN-05: < 5% reincidente
    EXPULSION_AGUA("expulsion_agua");                   // RN-05: >= 5%

    val rechazaLote: Boolean get() = this == RECHAZADO_ACIDEZ || this == EXPULSION_AGUA

    companion object {
        fun desde(clave: String) = entries.firstOrNull { it.clave == clave } ?: APROBADO
    }
}

enum class TipoSancion(val clave: String) {
    ADVERTENCIA_DESCUENTO("advertencia_descuento"),
    DESCUENTO_Y_RETIRO("descuento_y_retiro"),
    EXPULSION_TARIFA_MINIMA("expulsion_tarifa_minima"),
    CAPACITACION_OBLIGATORIA("capacitacion_obligatoria");

    companion object {
        fun desde(clave: String) = entries.firstOrNull { it.clave == clave }
    }
}

enum class TipoMovimientoStock(val clave: String, val signo: Int) {
    PRODUCCION_INGRESO("produccion_ingreso", +1),
    VENTA_EGRESO("venta_egreso", -1),
    AJUSTE_POSITIVO("ajuste_positivo", +1),
    AJUSTE_NEGATIVO("ajuste_negativo", -1),
    MERMA("merma", -1);

    companion object {
        fun desde(clave: String) = entries.firstOrNull { it.clave == clave }
    }
}

/**
 * Verificación en planta de una entrega concreta. La fija el jefe de producción
 * al recibir la ruta; el móvil solo la LEE (llega por la bajada de sync).
 */
enum class EstadoRecepcion(val clave: String) {
    PENDIENTE("pendiente"),   // el jefe aún no revisa esta entrega
    CONFORME("conforme"),     // recibió lo que el acopiador declaró
    FALTANTE("faltante"),     // el medidor de planta dio menos: faltó leche
    EXCEDENTE("excedente");   // el medidor de planta dio más

    val verificado: Boolean get() = this != PENDIENTE

    companion object {
        fun desde(clave: String?) = entries.firstOrNull { it.clave == clave } ?: PENDIENTE
    }
}

/** Ciclo de vida de una jornada de ruta en el móvil. */
enum class EstadoJornada(val clave: String) {
    EN_CURSO("en_curso"),
    CERRADA("cerrada"),
    DESCARGADA("descargada"),
    CONCILIADA("conciliada");

    companion object {
        fun desde(clave: String) = entries.firstOrNull { it.clave == clave } ?: EN_CURSO
    }
}

enum class TipoCliente(val clave: String) {
    MAYORISTA("mayorista"), SOCIO("socio"), PUBLICO("publico");

    companion object {
        fun desde(clave: String) = entries.firstOrNull { it.clave == clave } ?: PUBLICO
    }
}

/** Estado de sincronización de una fila local frente al servidor. */
enum class EstadoSincronizacionRegistro {
    /** Nace en el móvil, aún no confirmada por el servidor. */
    PENDIENTE,

    /** Confirmada por el servidor (aceptada o idempotente). */
    SINCRONIZADO,

    /** El servidor la rechazó o superó los reintentos: requiere atención en la UI. */
    CONFLICTO,
}
