package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.domain.vo.Dinero
import pe.edu.upeu.milkflow.domain.vo.Litros

/**
 * Precio historizado. Cubre tanto precio de venta por tipo de cliente como la
 * tarifa de compra de leche (según `concepto`). El móvil lo lee; gana el servidor.
 */
data class Precio(
    override val id: String,
    val concepto: ConceptoPrecio,
    val productoId: String?,          // null para tarifa de compra de leche
    val tipoCliente: TipoCliente?,    // null para tarifa de compra de leche
    val valor: Double,
    val valorMinimo: Double?,         // tarifa mínima RN-05 (solo compra de leche)
    val vigenteDesde: LocalDate,
    val vigenteHasta: LocalDate?,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable {
    fun vigenteEn(fecha: LocalDate): Boolean =
        vigenteDesde <= fecha && (vigenteHasta == null || vigenteHasta >= fecha)
}

enum class ConceptoPrecio { VENTA, COMPRA_LECHE }

data class Venta(
    override val id: String,
    val clienteId: String,
    val registradoPor: String,
    val tipoComprobante: String,
    val serieComprobante: String,
    val numeroComprobante: Long,
    val fecha: LocalDate,
    val total: Dinero,
    val estado: String,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable

/** Liquidación semanal del productor. La calcula el backend; el móvil la lee. */
data class Liquidacion(
    override val id: String,
    val semanaPagoId: String,
    val productorId: String,
    val litrosTotales: Litros,
    val precioLitroAplicado: Double,
    val tarifaDegradada: Boolean,
    val montoBruto: Dinero,
    val totalDescuentos: Dinero,
    val montoNeto: Dinero,
    val estado: String,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable
