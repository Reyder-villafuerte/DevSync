package com.example.milkflowmovil.dominio

import com.example.milkflowmovil.core.BooleanoFlexible
import com.example.milkflowmovil.core.DobleFlexible
import com.example.milkflowmovil.core.EnteroFlexible
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/**
 * Espejo local de las tablas del servidor.
 *
 * Los nombres de las columnas se conservan tal cual (snake_case) para que una
 * fila bajada y una creada sin señal en el teléfono sean la misma cosa.
 */

@Serializable
data class Zona(
    val id: Long,
    val code: String = "",
    val name: String = "",
    val description: String? = null,
    @Serializable(with = BooleanoFlexible::class) @SerialName("is_active") val activa: Boolean = true,
    @SerialName("updated_at") val actualizadoEn: String? = null,
)

@Serializable
data class Usuario(
    val id: Long,
    val name: String = "",
    val email: String = "",
    val role: String = "",
    val phone: String? = null,
    val dni: String? = null,
    @SerialName("zone_id") val zonaId: Long? = null,
    @Serializable(with = BooleanoFlexible::class) @SerialName("is_active") val activo: Boolean = true,
    @SerialName("updated_at") val actualizadoEn: String? = null,
) {
    val rol: Rol get() = Rol.desde(role)
}

@Serializable
data class Tarifa(
    val id: Long,
    @SerialName("season_name") val temporada: String = "",
    @Serializable(with = DobleFlexible::class) @SerialName("price_milk_base") val lecheBase: Double = 1.40,
    @Serializable(with = DobleFlexible::class) @SerialName("price_milk_water_penalty_low") val lecheAguaLeve: Double = 1.20,
    @Serializable(with = DobleFlexible::class) @SerialName("price_milk_water_penalty_high") val lecheAguaGrave: Double = 0.90,
    @Serializable(with = DobleFlexible::class) @SerialName("price_cheese_provider") val quesoProveedor: Double = 18.0,
    @Serializable(with = DobleFlexible::class) @SerialName("price_cheese_wholesale") val quesoMayorista: Double = 19.0,
    @Serializable(with = DobleFlexible::class) @SerialName("price_cheese_local") val quesoLocal: Double = 20.0,
    @Serializable(with = BooleanoFlexible::class) @SerialName("is_active") val activa: Boolean = true,
    val notes: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
)

@Serializable
data class Stock(
    val id: Long,
    @SerialName("item_code") val codigo: String = "",
    @SerialName("item_name") val nombre: String = "",
    @Serializable(with = DobleFlexible::class) @SerialName("current_stock") val cantidad: Double = 0.0,
    val unit: String = "",
    @SerialName("updated_at") val actualizadoEn: String? = null,
) {
    companion object {
        const val LECHE = "MILK_RAW_LITERS"
        const val QUESO = "CHEESE_MOLD_UNITS"
    }
}

@Serializable
data class Ruta(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    val date: String = "",
    @SerialName("zone_id") val zonaId: Long = 0,
    @SerialName("collector_id") val acopiadorId: Long = 0,
    @SerialName("start_time") val horaInicio: String? = "04:30:00",
    val status: String = "asignada",
    @Serializable(with = DobleFlexible::class) @SerialName("total_collected_liters") val litrosTotales: Double = 0.0,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    /** Solo local: la ruta aún no ha subido al servidor. */
    val pendiente: Boolean = false,
) {
    val estado: EstadoRuta get() = EstadoRuta.desde(status)
}

enum class EstadoRuta(val clave: String, val etiqueta: String) {
    ASIGNADA("asignada", "Asignada"),
    EN_RUTA("en_ruta", "En ruta"),
    DESCARGADA("descargada_planta", "Descargada en planta"),
    VERIFICADA("verificada", "Verificada con caudalímetro");

    companion object {
        fun desde(clave: String?) = entries.firstOrNull { it.clave == clave } ?: ASIGNADA
    }
}

@Serializable
data class Entrega(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("collection_route_id") val rutaId: Long = 0,
    @SerialName("producer_id") val productorId: Long = 0,
    @Serializable(with = DobleFlexible::class) val liters: Double = 0.0,
    @SerialName("collected_at") val hora: String? = null,
    val notes: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    /** Solo local: aún en la cola de subida. */
    val pendiente: Boolean = false,
    /** Solo local: uuid de la ruta cuando esta todavía no tiene id del servidor. */
    val rutaClientUuid: String? = null,
)

@Serializable
data class Recepcion(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("collection_route_id") val rutaId: Long = 0,
    @SerialName("verifier_id") val verificadorId: Long = 0,
    @Serializable(with = DobleFlexible::class) @SerialName("collector_declared_liters") val litrosDeclarados: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("flowmeter_liters") val litrosCaudalimetro: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("difference_liters") val diferencia: Double = 0.0,
    @SerialName("verification_status") val estado: String = "verificado",
    val observation: String? = null,
    @SerialName("verified_at") val verificadoEn: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

@Serializable
data class Cliente(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("first_name") val nombres: String = "",
    @SerialName("last_name") val apellidos: String = "",
    @SerialName("dni_ruc") val dniRuc: String? = null,
    val phone: String? = null,
    val type: String = "local",
    @SerialName("linked_user_id") val usuarioVinculadoId: Long? = null,
    @Serializable(with = BooleanoFlexible::class) @SerialName("is_wholesale_approved") val mayoristaAprobado: Boolean = false,
    @SerialName("updated_at") val actualizadoEn: String? = null,
) {
    val nombreCompleto: String get() = "$nombres $apellidos".trim()
}

@Serializable
data class Venta(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("receipt_number") val recibo: String = "",
    @SerialName("customer_id") val clienteId: Long = 0,
    @SerialName("seller_id") val vendedorId: Long = 0,
    @SerialName("closure_id") val cierreId: Long? = null,
    @Serializable(with = EnteroFlexible::class) @SerialName("cheese_molds_quantity") val moldes: Int = 0,
    @Serializable(with = DobleFlexible::class) @SerialName("unit_price") val precioUnitario: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("total_amount") val total: Double = 0.0,
    @SerialName("payment_method") val formaPago: String = "efectivo",
    @SerialName("sold_at") val vendidoEn: String = "",
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
) {
    val esDescuentoLeche: Boolean get() = formaPago == "descuento_leche"
}

@Serializable
data class CierreCaja(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    val date: String = "",
    @SerialName("closed_by") val cerradoPor: Long = 0,
    @Serializable(with = DobleFlexible::class) @SerialName("total_cash") val efectivo: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("total_milk_discount") val descuentoLeche: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("total_amount") val total: Double = 0.0,
    @Serializable(with = EnteroFlexible::class) @SerialName("cheese_molds_quantity") val moldes: Int = 0,
    @Serializable(with = EnteroFlexible::class) @SerialName("sales_count") val transacciones: Int = 0,
    val notes: String? = null,
    @SerialName("closed_at") val cerradoEn: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

@Serializable
data class Analisis(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("producer_id") val productorId: Long = 0,
    @SerialName("inspector_id") val inspectorId: Long = 0,
    @SerialName("analysis_date") val fecha: String = "",
    @Serializable(with = DobleFlexible::class) @SerialName("fat_percentage") val grasa: Double? = null,
    @Serializable(with = DobleFlexible::class) @SerialName("snf_percentage") val solidos: Double? = null,
    @Serializable(with = DobleFlexible::class) val density: Double? = null,
    @Serializable(with = DobleFlexible::class) @SerialName("protein_percentage") val proteina: Double? = null,
    @Serializable(with = DobleFlexible::class) @SerialName("water_addition_percentage") val agua: Double? = null,
    @Serializable(with = DobleFlexible::class) val temperature: Double? = null,
    @Serializable(with = DobleFlexible::class) @SerialName("ph_or_acidity") val acidez: Double? = null,
    val verdict: String = "conforme",
    val notes: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
) {
    val veredicto: Veredicto get() = Veredicto.desde(verdict)
}

enum class Veredicto(val clave: String, val etiqueta: String) {
    CONFORME("conforme", "Conforme"),
    ACIDEZ_ALTA("acidez_alta", "Acidez alta"),
    ADULTERADA("adulterada", "Adulterada"),
    SOSPECHOSA("sospechosa", "Sospechosa");

    companion object {
        fun desde(clave: String?) = entries.firstOrNull { it.clave == clave } ?: CONFORME
    }
}

@Serializable
data class VisitaTecnica(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("lactoscan_analysis_id") val analisisId: Long = 0,
    @SerialName("producer_id") val productorId: Long = 0,
    @SerialName("inspector_id") val inspectorId: Long = 0,
    @SerialName("scheduled_date") val fecha: String = "",
    @SerialName("scheduled_time") val hora: String? = null,
    val status: String = "programada",
    val reason: String = "",
    @SerialName("resolution_report") val informe: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

@Serializable
data class SolicitudZona(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("producer_id") val productorId: Long = 0,
    @SerialName("current_zone_id") val zonaActualId: Long = 0,
    @SerialName("requested_zone_id") val zonaSolicitadaId: Long = 0,
    val status: String = "pendiente",
    val reason: String? = null,
    @SerialName("reviewed_by") val revisadoPor: Long? = null,
    @SerialName("reviewed_at") val revisadoEn: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

@Serializable
data class Liquidacion(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("settlement_code") val codigo: String = "",
    @SerialName("producer_id") val productorId: Long = 0,
    @SerialName("start_date") val desde: String = "",
    @SerialName("end_date") val hasta: String = "",
    @Serializable(with = DobleFlexible::class) @SerialName("total_liters") val litros: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("price_per_liter") val precioLitro: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("gross_total") val bruto: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("deductions_total") val deducciones: Double = 0.0,
    @Serializable(with = DobleFlexible::class) @SerialName("net_total") val neto: Double = 0.0,
    val status: String = "pendiente",
    @SerialName("paid_at") val pagadoEn: String? = null,
    @SerialName("payment_method") val formaPago: String = "efectivo",
    @SerialName("paid_by") val pagadoPor: Long? = null,
    val notes: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

@Serializable
data class Descuento(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("producer_id") val productorId: Long = 0,
    @SerialName("settlement_id") val liquidacionId: Long? = null,
    @SerialName("sale_id") val ventaId: Long? = null,
    val date: String = "",
    val concept: String = "",
    @Serializable(with = DobleFlexible::class) val amount: Double = 0.0,
    val status: String = "pendiente",
    val notes: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
) {
    val esQueso: Boolean get() = concept.lowercase().contains("queso")
}

@Serializable
data class Egreso(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    val category: String = "otros",
    val description: String = "",
    @Serializable(with = DobleFlexible::class) val amount: Double = 0.0,
    @SerialName("expense_date") val fecha: String = "",
    @SerialName("user_id") val personalId: Long? = null,
    @SerialName("beneficiary_name") val beneficiario: String? = null,
    @SerialName("payment_method") val formaPago: String = "efectivo",
    @SerialName("receipt_number") val comprobante: String? = null,
    val notes: String? = null,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

@Serializable
data class Aviso(
    val id: Long,
    @SerialName("client_uuid") val clientUuid: String? = null,
    val title: String = "",
    val message: String = "",
    @SerialName("start_date") val desde: String = "",
    @SerialName("end_date") val hasta: String = "",
    @SerialName("target_role") val rolDestino: String? = null,
    @SerialName("target_user_id") val usuarioDestinoId: Long? = null,
    @Serializable(with = BooleanoFlexible::class) @SerialName("is_active") val activo: Boolean = true,
    @SerialName("updated_at") val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)
