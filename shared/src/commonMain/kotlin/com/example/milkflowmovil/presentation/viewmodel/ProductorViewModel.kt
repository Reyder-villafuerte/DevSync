package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.SolicitarCambioZonaUseCase

/** Portal del productor: su acopio, zona, descuentos, pagos y calidad. */
class ProductorViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val solicitarCambioZonaUseCase: SolicitarCambioZonaUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun solicitarCambioZona(zonaSolicitadaId: Long, motivo: String) =
        solicitarCambioZonaUseCase(zonaSolicitadaId, motivo)
}
