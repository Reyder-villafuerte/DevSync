package pe.edu.upeu.milkflow.data.mapper

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.ProblemaLeche
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.PruebaCalidad
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.model.RegistroSincronizable
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.model.TipoProducto
import pe.edu.upeu.milkflow.domain.model.Usuario

internal fun mapUsuario(
    id: String,
    nombreUsuario: String,
    nombre: String,
    rol: String,
    activo: Long,
): Usuario {
    val mappedRol = when (rol) {
        "ADMINISTRADOR" -> RolUsuario.ADMINISTRADORA
        "ENCARGADO_PLANTA" -> RolUsuario.JEFE_PRODUCCION
        "ENCARGADO_CALIDAD" -> RolUsuario.SUPERVISOR
        "COORDINADOR_ACOPIO" -> RolUsuario.SUPERVISOR
        "PRODUCTOR" -> RolUsuario.PRODUCTOR
        else -> try {
            RolUsuario.valueOf(rol)
        } catch (_: Exception) {
            RolUsuario.PENDIENTE_ASIGNACION
        }
    }
    return Usuario(id, nombreUsuario, nombre, mappedRol, activo == 1L)
}

internal fun mapProductor(id: String, nombre: String, activo: Long): Productor =
    Productor(id, nombre, activo == 1L)

internal fun mapAcopiador(id: String, nombre: String): Acopiador = Acopiador(id, nombre)

internal fun mapEntrega(
    id: String,
    productorId: String,
    fechaHoraEpochMillis: Long,
    litros: Double,
    tipo: String,
    usuarioRegistroId: String,
    estadoSincronizacion: String,
    acopiadorId: String?,
    sector: String?,
): Entrega = Entrega(
    id = id,
    productorId = productorId,
    fechaHora = Instant.fromEpochMilliseconds(fechaHoraEpochMillis),
    litros = litros,
    tipo = TipoEntrega.valueOf(tipo),
    usuarioRegistroId = usuarioRegistroId,
    estadoSincronizacion = EstadoSincronizacion.valueOf(estadoSincronizacion),
    acopiadorId = acopiadorId,
    sector = sector,
)

internal fun mapPruebaCalidad(
    id: String,
    entregaId: String,
    fechaHoraEpochMillis: Long,
): PruebaCalidad = PruebaCalidad(
    id = id,
    entregaId = entregaId,
    fechaHora = Instant.fromEpochMilliseconds(fechaHoraEpochMillis),
)

internal fun mapProblemaLeche(
    id: String,
    entregaId: String,
    descripcion: String,
    fechaHoraEpochMillis: Long,
): ProblemaLeche = ProblemaLeche(
    id = id,
    entregaId = entregaId,
    descripcion = descripcion,
    fechaHora = Instant.fromEpochMilliseconds(fechaHoraEpochMillis),
)

internal fun mapAuditoria(
    id: String,
    usuarioId: String,
    fechaHoraEpochMillis: Long,
    registroAfectadoId: String,
    accion: String,
): RegistroAuditoria = RegistroAuditoria(
    id = id,
    usuarioId = usuarioId,
    fechaHora = Instant.fromEpochMilliseconds(fechaHoraEpochMillis),
    registroAfectadoId = registroAfectadoId,
    accion = accion,
)

internal fun mapRegistroSincronizable(
    registroId: String,
    tipoRegistro: String,
    estado: String,
): RegistroSincronizable = RegistroSincronizable(
    registroId = registroId,
    tipoRegistro = tipoRegistro,
    estado = EstadoSincronizacion.valueOf(estado),
)

internal fun mapLoteProduccion(
    id: String,
    fechaHoraEpochMillis: Long,
    litrosUtilizados: Double,
    moldesObtenidos: Long,
    tipoProducto: String,
    observaciones: String?,
): LoteProduccion = LoteProduccion(
    id = id,
    fechaHora = Instant.fromEpochMilliseconds(fechaHoraEpochMillis),
    litrosLecheUtilizados = litrosUtilizados,
    moldesObtenidos = moldesObtenidos.toInt(),
    tipoProducto = TipoProducto.valueOf(tipoProducto),
    observaciones = observaciones,
)

internal fun Boolean.toSqlLong(): Long = if (this) 1L else 0L
