package com.example.milkflowmovil.domain.repository

import com.example.milkflowmovil.domain.model.SolicitudZona

/** Contrato de zonas: solicitud de cambio (productor) y revisión (administración). */
interface ZonasRepository {
    fun solicitarCambioZona(zonaSolicitadaId: Long, motivo: String)

    fun revisarSolicitudZona(solicitud: SolicitudZona, decision: String)
}
