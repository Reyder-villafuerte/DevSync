package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.EntregaInvalidaException
import pe.edu.upeu.milkflow.domain.LitrosInvalidosException

data class Entrega(
    val id: String,
    val productorId: String,
    val fechaHora: Instant,
    val litros: Double,
    val tipo: TipoEntrega,
    val usuarioRegistroId: String,
    val estadoSincronizacion: EstadoSincronizacion = EstadoSincronizacion.PENDIENTE,
    val acopiadorId: String? = null,
    val sector: String? = null,
) {
    init {
        require(id.isNotBlank()) { "El identificador de la entrega es obligatorio." }
        require(productorId.isNotBlank()) { "La entrega debe estar asociada a un productor." }
        require(usuarioRegistroId.isNotBlank()) { "Debe indicarse quién registró la entrega." }

        if (!litros.isFinite() || litros <= 0.0) {
            throw LitrosInvalidosException(litros)
        }

        when (tipo) {
            TipoEntrega.DIRECTA -> {
                if (acopiadorId != null || sector != null) {
                    throw EntregaInvalidaException(
                        "Una entrega directa no debe asociar acopiador ni sector.",
                    )
                }
            }

            TipoEntrega.RECOGIDA -> {
                if (acopiadorId.isNullOrBlank() || sector.isNullOrBlank()) {
                    throw EntregaInvalidaException(
                        "Una entrega recogida debe asociar un acopiador y un sector.",
                    )
                }
            }
        }
    }
}
