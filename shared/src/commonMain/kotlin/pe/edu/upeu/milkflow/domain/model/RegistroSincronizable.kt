package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant

/**
 * Contrato común de toda entidad que viaja por el protocolo de sincronización,
 * espejo de las columnas del backend:
 *  - id        : UUID generado en cliente.
 *  - updatedAt : sello del servidor; base del cursor de bajada. En una fila
 *                recién creada offline lleva la hora local hasta que el
 *                servidor la confirme.
 *  - version   : entero monótono del servidor; detector de conflictos.
 *  - deleted   : borrado lógico (nunca se borra la fila físicamente).
 */
interface RegistroSincronizable {
    val id: String
    val updatedAt: Instant
    val version: Long
    val deleted: Boolean
}

/**
 * Metadatos locales que NO viajan al servidor pero la UI necesita: en qué
 * estado de sincronización está la fila y, si hubo conflicto, por qué.
 */
data class MetaSync(
    val estado: EstadoSincronizacionRegistro,
    val intentos: Int = 0,
    val ultimoError: String? = null,
)
