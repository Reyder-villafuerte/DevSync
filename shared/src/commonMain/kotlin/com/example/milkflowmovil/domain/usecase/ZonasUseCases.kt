package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.domain.model.SolicitudZona
import com.example.milkflowmovil.domain.repository.ZonasRepository

class SolicitarCambioZonaUseCase(private val repositorio: ZonasRepository) {
    operator fun invoke(zonaSolicitadaId: Long, motivo: String) =
        repositorio.solicitarCambioZona(zonaSolicitadaId, motivo)
}

class RevisarSolicitudZonaUseCase(private val repositorio: ZonasRepository) {
    operator fun invoke(solicitud: SolicitudZona, decision: String) =
        repositorio.revisarSolicitudZona(solicitud, decision)
}
