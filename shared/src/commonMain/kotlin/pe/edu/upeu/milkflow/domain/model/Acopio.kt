package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.domain.vo.Litros

/**
 * Cabecera del recorrido de un acopiador en un día. Se crea offline al iniciar
 * ruta y se "cierra" al descargar en tina.
 */
data class JornadaRuta(
    override val id: String,
    val acopiadorId: String,
    val rutaId: String,
    val dispositivoId: String?,
    val fecha: LocalDate,
    val horaInicio: Instant,
    val horaCierre: Instant?,
    val litrosDeclarados: Litros,
    val estado: EstadoJornada,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable

/**
 * Litros que un productor entrega en un recorrido. Es el hecho económico que
 * alimenta la liquidación semanal. En el móvil es SOLO INSERCIÓN.
 */
data class Recoleccion(
    override val id: String,
    val jornadaId: String,
    val productorId: String,
    val litros: Litros,
    val horaRegistro: Instant,
    val observacion: String?,
    val sospechaAdulteracion: Boolean,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
    // --- Verificación de recepción en planta (autoría del jefe de producción) ---
    // El móvil solo lee estos campos; llegan por la bajada de sync.
    val estadoRecepcion: EstadoRecepcion = EstadoRecepcion.PENDIENTE,
    val litrosRecibidos: Litros? = null,
    val litrosFaltantes: Litros = Litros.CERO,
) : RegistroSincronizable

/**
 * Descarga en tina de planta al cerrar la ruta (equivale a `descargas_tina`).
 */
data class Recepcion(
    override val id: String,
    val jornadaId: String,
    val tina: String?,
    val litrosDescargados: Litros,
    val horaDescarga: Instant,
    val recibidoPor: String?,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable
