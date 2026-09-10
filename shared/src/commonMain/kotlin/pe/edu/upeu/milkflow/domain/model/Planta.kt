package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.domain.vo.Litros

data class SesionProduccion(
    override val id: String,
    val productoId: String,
    val jefeProduccionId: String,
    val loteCodigo: String,
    val fecha: LocalDate,
    val litrosProcesados: Litros,
    val unidadesProducidas: Int?,
    val rendimientoPor100L: Double?,
    val cumpleRn08: Boolean?,
    val estado: String,
    val completadaEn: Instant?,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable

/**
 * Evento del libro de stock. Cantidad CON SIGNO. En el móvil es CONMUTATIVO:
 * cada id se inserta una vez y el orden no altera el saldo.
 */
data class MovimientoStock(
    override val id: String,
    val productoId: String,
    val tipo: TipoMovimientoStock,
    val cantidad: Double,
    val origenTipo: String?,
    val origenId: String?,
    val registradoPor: String,
    val ocurridoEn: Instant,
    val motivo: String?,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable
