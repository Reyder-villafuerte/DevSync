package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.domain.vo.Dni

data class Usuario(
    override val id: String,
    val nombres: String,
    val apellidos: String,
    val dni: Dni,
    val rol: RolUsuario,
    val activo: Boolean,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable {
    val nombreCompleto get() = "$nombres $apellidos"
}

data class Productor(
    override val id: String,
    val codigoPadron: String,
    val nombres: String,
    val apellidos: String,
    val dni: Dni,
    val zonaId: String,
    val telefono: String?,
    val estado: EstadoProductor,
    val fechaIngreso: LocalDate,
    override val updatedAt: Instant,
    override val version: Long,
    override val deleted: Boolean = false,
) : RegistroSincronizable {
    val nombreCompleto get() = "$nombres $apellidos"
    val enPadron: Boolean get() = estado == EstadoProductor.ACTIVO || estado == EstadoProductor.SUSPENDIDO
}
