package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.vo.MedicionLactoscan

/**
 * Control inopinado de calidad. Guarda las mediciones y el dictamen JUNTOS: el
 * dictamen se congela al momento del control y no se recalcula al leer (igual
 * criterio que el backend). En el móvil es SOLO INSERCIÓN.
 */
data class Inspeccion(
    override val id: String,
    val productorId: String,
    val supervisorId: String,
    val jornadaId: String?,
    val recoleccionId: String?,
    val tomadoEn: Instant,
    val medicion: MedicionLactoscan,
    val dictamen: DictamenCalidad,
    val dictamenDetalle: String,
    val esReincidencia: Boolean,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable

/**
 * Consecuencia de un dictamen. La calcula el backend; el móvil solo la lee.
 */
data class Sancion(
    override val id: String,
    val productorId: String,
    val inspeccionId: String,
    val semanaPagoId: String?,
    val tipo: TipoSancion,
    val porcentajeDescuento: Double?,
    val montoDescuento: Double?,
    val tarifaDegradadaLitro: Double?,
    val retiraDelPadron: Boolean,
    val expulsa: Boolean,
    val estado: String,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable
