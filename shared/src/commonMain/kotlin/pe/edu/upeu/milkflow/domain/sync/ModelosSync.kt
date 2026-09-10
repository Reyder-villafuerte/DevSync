package pe.edu.upeu.milkflow.domain.sync

import kotlin.time.Instant

/** Tipo de operación encolada en el outbox. */
enum class TipoOperacion { INSERTAR, ACTUALIZAR, ELIMINAR }

/**
 * Estrategia de resolución de conflicto por dominio, espejo del backend.
 * La usa el SyncManager para decidir qué hacer cuando el servidor responde
 * un conflicto sobre una operación subida.
 */
enum class EstrategiaConflicto {
    /** Recolecciones, inspecciones, recepciones: el móvil solo inserta. */
    SOLO_INSERCION,

    /** Movimientos de stock: conmutativos; un id ya presente = no-op. */
    CONMUTATIVO,

    /** Precios, avisos, liquidaciones: gana el servidor. */
    SERVIDOR_GANA,

    /** Cabecera de jornada: concurrencia optimista por `version`. */
    VERSION;

    companion object {
        /**
         * Mapa entidad -> estrategia. Las claves son los nombres de entidad del
         * backend (config/sync.php), que es lo que viaja en cada operación de
         * `/api/sync/push`. `avisos_vistos` es especial: se sube por el endpoint
         * dedicado POST /api/avisos/{id}/visto.
         */
        private val PORTABLA = mapOf(
            "registros_acopio" to SOLO_INSERCION,
            "controles_calidad" to SOLO_INSERCION,
            "descargas_tina" to SOLO_INSERCION,
            "asistencias" to SOLO_INSERCION,
            "avisos_vistos" to SOLO_INSERCION,
            "movimientos_stock" to CONMUTATIVO,
            "rutas_acopio" to VERSION,
            "solicitudes_cambio_zona" to VERSION,
        )

        fun deTabla(tabla: String): EstrategiaConflicto = PORTABLA[tabla] ?: VERSION
    }
}

/** Entidades de sincronización usadas por el cliente móvil. */
object Entidades {
    const val JORNADA = "rutas_acopio"
    const val RECOLECCION = "registros_acopio"
    const val RECEPCION = "descargas_tina"
    const val INSPECCION = "controles_calidad"
    const val SOLICITUD_RUTA = "solicitudes_cambio_zona"
    const val MOVIMIENTO_STOCK = "movimientos_stock"
    const val AVISO_VISTO = "avisos_vistos"
}

/**
 * Fila del outbox: una escritura local pendiente de confirmar con el servidor.
 * Se crea en la MISMA transacción que la escritura de negocio.
 */
data class OperacionOutbox(
    val idLocal: Long,
    val operacion: TipoOperacion,
    val tabla: String,           // nombre de entidad de sincronización (backend)
    val idRegistro: String,      // UUID del registro afectado
    val payloadJson: String,     // cuerpo listo para /api/sync/push
    val versionBase: Long,       // versión del servidor conocida (0 si nace local)
    val intentos: Int,
    val ultimoError: String?,
    val creadoEn: Instant,
) {
    val estrategia: EstrategiaConflicto get() = EstrategiaConflicto.deTabla(tabla)
}

// ---- Resultado de la subida (respuesta de POST /api/sync/push) ----

data class OperacionAceptada(val idRegistro: String, val tabla: String, val version: Long, val resultado: String)
data class OperacionEnConflicto(val idRegistro: String, val tabla: String, val motivo: String, val servidorJson: String?)
data class OperacionRechazada(val idRegistro: String, val tabla: String, val motivo: String)

data class ResultadoSubida(
    val aceptadas: List<OperacionAceptada>,
    val conflictos: List<OperacionEnConflicto>,
    val rechazadas: List<OperacionRechazada>,
)

/** Resumen de una página de bajada. El detalle ya se persistió en local. */
data class ResumenBajada(
    val cursor: String,
    val hayMas: Boolean,
    val servidorEn: Instant,
    val filasAplicadas: Int,
)

/**
 * Estado observable para el indicador del encabezado de la app.
 */
data class EstadoSincronizacion(
    val pendientes: Int = 0,
    val sincronizando: Boolean = false,
    val ultimoExito: Instant? = null,
    val conflictos: Int = 0,
) {
    val alDia: Boolean get() = pendientes == 0 && conflictos == 0 && !sincronizando
}
