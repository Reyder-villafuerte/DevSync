package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.data.local.nuevoUuid
import com.example.milkflowmovil.domain.model.SolicitudZona
import com.example.milkflowmovil.domain.repository.ZonasRepository
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/** Zonas: solicitud de cambio del productor y revisión de administración. */
class ZonasRepositoryImpl(
    private val cola: ColaOperaciones,
) : ZonasRepository {

    private val base get() = cola.base
    private val yo get() = cola.yo

    override fun solicitarCambioZona(zonaSolicitadaId: Long, motivo: String) {
        val usuario = yo ?: return
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                solicitudesZona = estado.solicitudesZona + SolicitudZona(
                    id = idNuevo,
                    clientUuid = uuid,
                    productorId = usuario.id,
                    zonaActualId = usuario.zonaId ?: zonaSolicitadaId,
                    zonaSolicitadaId = zonaSolicitadaId,
                    status = "pendiente",
                    reason = motivo,
                    pendiente = true,
                )
            )
        }

        cola.encolar(
            uuid, "solicitar_cambio_zona",
            buildJsonObject {
                put("requested_zone_id", zonaSolicitadaId)
                put("reason", motivo)
            },
            "Solicitud de cambio a ${base.actual.zona(zonaSolicitadaId)?.name ?: "otra zona"}",
        )
    }

    override fun revisarSolicitudZona(solicitud: SolicitudZona, decision: String) {
        if (solicitud.id <= 0) return
        val usuario = yo ?: return
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(
                solicitudesZona = estado.solicitudesZona.map {
                    if (it.id == solicitud.id) {
                        it.copy(status = decision, revisadoPor = usuario.id, revisadoEn = Fechas.ahoraIso(), pendiente = true)
                    } else {
                        it
                    }
                },
                usuarios = if (decision == "aprobado") {
                    estado.usuarios.map {
                        if (it.id == solicitud.productorId) it.copy(zonaId = solicitud.zonaSolicitadaId) else it
                    }
                } else {
                    estado.usuarios
                },
            )
        }

        cola.encolar(
            uuid, "revisar_solicitud_zona",
            buildJsonObject {
                put("request_id", solicitud.id)
                put("decision", decision)
            },
            "Solicitud de zona ${if (decision == "aprobado") "aprobada" else "rechazada"}",
        )
    }
}
