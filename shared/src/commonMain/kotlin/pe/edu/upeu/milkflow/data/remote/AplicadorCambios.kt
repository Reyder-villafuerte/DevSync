package pe.edu.upeu.milkflow.data.remote

import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.withContext
import kotlin.time.Instant
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.booleanOrNull
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.longOrNull
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase

/**
 * Aplica el `cambios` de una bajada a la BD local. Es la contraparte "escritura
 * remota" de los mapeadores: convierte cada fila JSON (camelCase, como la envía
 * el backend) en un upsert.
 *
 * Todo el delta se aplica en UNA transacción: o entra completo o no entra, así
 * el cursor nunca avanza sobre un estado local a medias.
 *
 * Los `INSERT OR REPLACE` marcan la fila como SINCRONIZADO; una fila que el
 * dispositivo generó y ya subió vuelve aquí con su version/updated_at reales.
 */
class AplicadorCambios(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) {
    private val q get() = db.milkFlowQueries

    suspend fun aplicar(cambios: Map<String, List<JsonObject>>): Int = withContext(io) {
        var total = 0
        db.transaction {
            cambios.forEach { (entidad, filas) ->
                filas.forEach { fila ->
                    if (aplicarFila(entidad, fila)) total++
                }
            }
        }
        total
    }

    private fun aplicarFila(entidad: String, o: JsonObject): Boolean {
        when (entidad) {
            "rutas" -> q.upsertRuta(
                o.str("id"), o.str("nombre"), o.strOrNull("codigo"),
                o.boolLong("activa", true), o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "zonas" -> q.upsertZona(
                o.str("id"), o.str("nombre"), o.strOrNull("codigo"), o.str("rutaId"),
                o.boolLong("activa", true), o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "productores" -> q.upsertProductor(
                o.str("id"), o.str("codigoPadron"), o.str("nombres"), o.str("apellidos"), o.str("dni"),
                o.str("zonaId"), o.strOrNull("telefono"), o.str("estado"), o.str("fechaIngreso"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "preciosVenta" -> q.upsertPrecio(
                o.str("id"), "VENTA", o.strOrNull("productoId"), o.strOrNull("tipoCliente"),
                o.dbl("precio"), null, o.str("vigenteDesde"), o.strOrNull("vigenteHasta"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "preciosCompraLeche" -> q.upsertPrecio(
                o.str("id"), "COMPRA_LECHE", null, null,
                o.dbl("precioLitro"), o.dblOrNull("precioLitroMinimo"), o.str("vigenteDesde"), o.strOrNull("vigenteHasta"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "avisos" -> q.upsertAviso(
                o.str("id"), o.str("titulo"), o.str("contenido"), o.strOrNull("imagenUrl"),
                o.boolLong("obligatorio"), o.millis("fechaPublicacion"), o.millisOrNull("fechaExpiracion"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "sanciones" -> q.upsertSancion(
                o.str("id"), o.str("productorId"), o.str("controlCalidadId"), o.strOrNull("semanaPagoId"),
                o.str("tipo"), o.dblOrNull("porcentajeDescuento"), o.dblOrNull("montoDescuento"),
                o.dblOrNull("tarifaDegradadaLitro"), o.boolLong("retiraDelPadron"), o.boolLong("expulsa"),
                o.str("estado"), o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "liquidaciones" -> q.upsertLiquidacion(
                o.str("id"), o.str("semanaPagoId"), o.str("productorId"), o.dbl("litrosTotales"),
                o.dbl("precioLitroAplicado"), o.boolLong("tarifaDegradada"), o.dbl("montoBruto"),
                o.dbl("totalDescuentos"), o.dbl("montoNeto"), o.str("estado"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "rutasAcopio" -> q.upsertJornadaRemota(
                o.str("id"), o.str("acopiadorId"), o.str("rutaId"), o.strOrNull("dispositivoId"),
                o.str("fecha"), o.millis("horaInicio"), o.millisOrNull("horaCierre"),
                o.dbl("litrosDeclarados"), o.str("estado"), o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "registrosAcopio" -> q.upsertRecoleccionRemota(
                o.str("id"), o.str("rutaAcopioId"), o.str("productorId"), o.dbl("litros"),
                o.millis("horaRegistro"), o.strOrNull("observacion"), o.boolLong("sospechaAdulteracion"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
                o.strOrNull("estadoRecepcion") ?: "pendiente", o.dblOrNull("litrosRecibidos"), o.dbl("litrosFaltantes"),
            )
            "solicitudesCambioZona" -> q.upsertSolicitudRutaRemota(
                o.str("id"), o.str("productorId"), o.str("zonaActualId"), o.str("zonaSolicitadaId"),
                o.str("motivo"), o.str("estado"), o.millisOrNull("resueltoEn"), o.strOrNull("comentarioResolucion"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            "movimientosStock" -> q.upsertMovimientoStockRemoto(
                o.str("id"), o.str("productoId"), o.str("tipoMovimiento"), o.dbl("cantidad"),
                o.strOrNull("origenTipo"), o.strOrNull("origenId"), o.str("registradoPor"),
                o.millis("ocurridoEn"), o.strOrNull("motivo"),
                o.millis("updatedAt"), o.long("version"), o.boolLong("deleted"),
            )
            // productos, ventas, clientes, etc. no se usan aún en el móvil.
            else -> return false
        }
        // Al aplicar una fila del servidor, si teníamos una operación pendiente
        // de ese registro y coincide la versión, ya no hace falta subirla.
        q.eliminarOutboxPorRegistro(entidadOutbox(entidad), o.str("id"))
        return true
    }

    private fun entidadOutbox(entidadCamel: String): String = when (entidadCamel) {
        "rutasAcopio" -> "rutas_acopio"
        "registrosAcopio" -> "registros_acopio"
        "solicitudesCambioZona" -> "solicitudes_cambio_zona"
        "movimientosStock" -> "movimientos_stock"
        else -> entidadCamel
    }
}

// ---- helpers de lectura de JsonObject ----
private fun JsonObject.prim(k: String): JsonPrimitive? = (this[k] as? JsonPrimitive)?.takeIf { it.content != "null" }
private fun JsonObject.str(k: String): String = prim(k)?.content ?: error("falta campo '$k'")
private fun JsonObject.strOrNull(k: String): String? = prim(k)?.content
private fun JsonObject.long(k: String): Long = prim(k)?.longOrNull ?: prim(k)?.content?.toLongOrNull() ?: 0L
private fun JsonObject.dbl(k: String): Double = prim(k)?.content?.toDoubleOrNull() ?: 0.0
private fun JsonObject.dblOrNull(k: String): Double? = prim(k)?.content?.toDoubleOrNull()
private fun JsonObject.boolLong(k: String, pordefecto: Boolean = false): Long {
    val v = prim(k) ?: return if (pordefecto) 1L else 0L
    val b = v.booleanOrNull ?: (v.content == "1" || v.content == "true")
    return if (b) 1L else 0L
}
private fun JsonObject.millis(k: String): Long =
    prim(k)?.content?.let { runCatching { Instant.parse(it).toEpochMilliseconds() }.getOrNull() ?: it.toLongOrNull() } ?: 0L
private fun JsonObject.millisOrNull(k: String): Long? =
    prim(k)?.content?.let { runCatching { Instant.parse(it).toEpochMilliseconds() }.getOrNull() ?: it.toLongOrNull() }
