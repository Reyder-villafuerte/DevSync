package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.model.SolicitudZona
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.RevisarSolicitudZonaUseCase

/** Zonas 1 a 4 y solicitudes de cambio (administración). */
class ZonasViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val revisarSolicitudZonaUseCase: RevisarSolicitudZonaUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun revisarSolicitudZona(solicitud: SolicitudZona, decision: String) =
        revisarSolicitudZonaUseCase(solicitud, decision)
}
