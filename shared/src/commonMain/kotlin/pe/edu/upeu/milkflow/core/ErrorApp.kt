package pe.edu.upeu.milkflow.core

/**
 * Error de dominio cerrado. La capa remota traduce cada respuesta HTTP a uno
 * de estos casos; la UI decide qué mensaje mostrar sin conocer detalles de red.
 */
sealed class ErrorApp(val mensaje: String, val causa: Throwable? = null) {

    /** No hay conectividad o el host no respondió. La operación se reintentará. */
    class SinRed(causa: Throwable? = null) :
        ErrorApp("Sin conexión. Se sincronizará cuando vuelva la red.", causa)

    /** 401/403: token inválido o expirado. La UI debe forzar re-login. */
    data object NoAutorizado : ErrorApp("Su sesión expiró. Vuelva a iniciar sesión.")

    /** El rol no puede realizar esta operación (403 con permiso). */
    data object Prohibido : ErrorApp("Su rol no tiene permiso para esta acción.")

    /**
     * 207/409: el servidor tiene una versión más nueva de este registro.
     * Se adjunta el estado del servidor para que la UI lo muestre.
     */
    data class ConflictoVersion(
        val idRegistro: String,
        val tabla: String,
        val servidorJson: String?,
    ) : ErrorApp("El registro cambió en el servidor y no se pudo aplicar el cambio local.")

    /** 422 stock_insuficiente al intentar registrar una venta. */
    data class StockInsuficiente(
        val idProducto: String,
        val disponible: Double,
        val requerido: Double,
    ) : ErrorApp("Stock insuficiente: disponible $disponible, se requieren $requerido.")

    /** 422 genérico de regla de negocio del backend. */
    data class ReglaNegocio(val regla: String, val detalle: String) : ErrorApp(detalle)

    /** 5xx. Reintentable. */
    data class ErrorServidor(val codigo: Int) : ErrorApp("Error del servidor ($codigo). Reintentando…")

    /** Fallo de persistencia local (SQLite). NO reintentable por red. */
    class ErrorLocal(causa: Throwable? = null) : ErrorApp("Error al guardar en el dispositivo.", causa)

    /** Datos inválidos detectados antes de tocar red o disco. */
    data class Validacion(val campo: String, val motivo: String) : ErrorApp("$campo: $motivo")

    /** Se agotaron los reintentos de sincronización de un registro. */
    data class SincronizacionAgotada(val idRegistro: String, val ultimoError: String) :
        ErrorApp("No se pudo sincronizar el registro tras varios intentos.")

    /** ¿Conviene volver a intentar automáticamente esta operación? */
    val esReintentable: Boolean
        get() = this is SinRed || this is ErrorServidor
}
