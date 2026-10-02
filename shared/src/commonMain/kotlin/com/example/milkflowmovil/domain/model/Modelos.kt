package com.example.milkflowmovil.domain.model

/**
 * Modelos de dominio de Huata.
 *
 * Son clases Kotlin puras: no saben que existe JSON ni la API. La forma en que
 * llegan del servidor (nombres snake_case, decimales como texto) vive en los
 * DTO de `data/remote/dto`, y la traducción en `data/mapper` con `toDomain()`.
 */

data class Zona(
    val id: Long,
    val code: String = "",
    val name: String = "",
    val description: String? = null,
    val activa: Boolean = true,
    val actualizadoEn: String? = null,
)

data class Usuario(
    val id: Long,
    val name: String = "",
    val email: String = "",
    val role: String = "",
    val phone: String? = null,
    val dni: String? = null,
    val zonaId: Long? = null,
    val activo: Boolean = true,
    val actualizadoEn: String? = null,
) {
    val rol: Rol get() = Rol.desde(role)
}

data class Tarifa(
    val id: Long,
    val temporada: String = "",
    val lecheBase: Double = 1.40,
    val lecheAguaLeve: Double = 1.20,
    val lecheAguaGrave: Double = 0.90,
    val quesoProveedor: Double = 18.0,
    val quesoMayorista: Double = 19.0,
    val quesoLocal: Double = 20.0,
    val activa: Boolean = true,
    val notes: String? = null,
    val actualizadoEn: String? = null,
)

data class Stock(
    val id: Long,
    val codigo: String = "",
    val nombre: String = "",
    val cantidad: Double = 0.0,
    val unit: String = "",
    val actualizadoEn: String? = null,
) {
    companion object {
        const val LECHE = "MILK_RAW_LITERS"
        const val QUESO = "CHEESE_MOLD_UNITS"
    }
}

data class Ruta(
    val id: Long,
    val clientUuid: String? = null,
    val date: String = "",
    val zonaId: Long = 0,
    val acopiadorId: Long = 0,
    val horaInicio: String? = "04:30:00",
    val status: String = "asignada",
    val litrosTotales: Double = 0.0,
    val actualizadoEn: String? = null,
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

data class Entrega(
    val id: Long,
    val clientUuid: String? = null,
    val rutaId: Long = 0,
    val productorId: Long = 0,
    val liters: Double = 0.0,
    val hora: String? = null,
    val notes: String? = null,
    val actualizadoEn: String? = null,
    /** Solo local: aún en la cola de subida. */
    val pendiente: Boolean = false,
    /** Solo local: uuid de la ruta cuando esta todavía no tiene id del servidor. */
    val rutaClientUuid: String? = null,
)

data class Recepcion(
    val id: Long,
    val clientUuid: String? = null,
    val rutaId: Long = 0,
    val verificadorId: Long = 0,
    val litrosDeclarados: Double = 0.0,
    val litrosCaudalimetro: Double = 0.0,
    val diferencia: Double = 0.0,
    val estado: String = "verificado",
    val observation: String? = null,
    val verificadoEn: String? = null,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

data class Cliente(
    val id: Long,
    val clientUuid: String? = null,
    val nombres: String = "",
    val apellidos: String = "",
    val dniRuc: String? = null,
    val phone: String? = null,
    val type: String = "local",
    val usuarioVinculadoId: Long? = null,
    val mayoristaAprobado: Boolean = false,
    val actualizadoEn: String? = null,
) {
    val nombreCompleto: String get() = "$nombres $apellidos".trim()
}

data class Venta(
    val id: Long,
    val clientUuid: String? = null,
    val recibo: String = "",
    val clienteId: Long = 0,
    val vendedorId: Long = 0,
    val cierreId: Long? = null,
    val moldes: Int = 0,
    val precioUnitario: Double = 0.0,
    val total: Double = 0.0,
    val formaPago: String = "efectivo",
    val vendidoEn: String = "",
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
) {
    val esDescuentoLeche: Boolean get() = formaPago == "descuento_leche"
}

data class CierreCaja(
    val id: Long,
    val clientUuid: String? = null,
    val date: String = "",
    val cerradoPor: Long = 0,
    val efectivo: Double = 0.0,
    val descuentoLeche: Double = 0.0,
    val total: Double = 0.0,
    val moldes: Int = 0,
    val transacciones: Int = 0,
    val notes: String? = null,
    val cerradoEn: String? = null,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

data class Analisis(
    val id: Long,
    val clientUuid: String? = null,
    val productorId: Long = 0,
    val inspectorId: Long = 0,
    val fecha: String = "",
    val grasa: Double? = null,
    val solidos: Double? = null,
    val density: Double? = null,
    val proteina: Double? = null,
    val agua: Double? = null,
    val temperature: Double? = null,
    val acidez: Double? = null,
    val verdict: String = "conforme",
    val notes: String? = null,
    val actualizadoEn: String? = null,
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

data class VisitaTecnica(
    val id: Long,
    val clientUuid: String? = null,
    val analisisId: Long = 0,
    val productorId: Long = 0,
    val inspectorId: Long = 0,
    val fecha: String = "",
    val hora: String? = null,
    val status: String = "programada",
    val reason: String = "",
    val informe: String? = null,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

data class SolicitudZona(
    val id: Long,
    val clientUuid: String? = null,
    val productorId: Long = 0,
    val zonaActualId: Long = 0,
    val zonaSolicitadaId: Long = 0,
    val status: String = "pendiente",
    val reason: String? = null,
    val revisadoPor: Long? = null,
    val revisadoEn: String? = null,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

data class Liquidacion(
    val id: Long,
    val clientUuid: String? = null,
    val codigo: String = "",
    val productorId: Long = 0,
    val desde: String = "",
    val hasta: String = "",
    val litros: Double = 0.0,
    val precioLitro: Double = 0.0,
    val bruto: Double = 0.0,
    val deducciones: Double = 0.0,
    val neto: Double = 0.0,
    val status: String = "pendiente",
    val pagadoEn: String? = null,
    val formaPago: String = "efectivo",
    val pagadoPor: Long? = null,
    val notes: String? = null,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

data class Descuento(
    val id: Long,
    val clientUuid: String? = null,
    val productorId: Long = 0,
    val liquidacionId: Long? = null,
    val ventaId: Long? = null,
    val date: String = "",
    val concept: String = "",
    val amount: Double = 0.0,
    val status: String = "pendiente",
    val notes: String? = null,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
) {
    val esQueso: Boolean get() = concept.lowercase().contains("queso")
}

data class Egreso(
    val id: Long,
    val clientUuid: String? = null,
    val category: String = "otros",
    val description: String = "",
    val amount: Double = 0.0,
    val fecha: String = "",
    val personalId: Long? = null,
    val beneficiario: String? = null,
    val formaPago: String = "efectivo",
    val comprobante: String? = null,
    val notes: String? = null,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)

data class Aviso(
    val id: Long,
    val clientUuid: String? = null,
    val title: String = "",
    val message: String = "",
    val desde: String = "",
    val hasta: String = "",
    val rolDestino: String? = null,
    val usuarioDestinoId: Long? = null,
    val activo: Boolean = true,
    val actualizadoEn: String? = null,
    val pendiente: Boolean = false,
)
