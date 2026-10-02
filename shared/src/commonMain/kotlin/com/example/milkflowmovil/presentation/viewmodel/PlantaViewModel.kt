package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.model.Ruta
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.VerificarRecepcionUseCase

/** Caudalímetro de planta. */
class PlantaViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val verificarRecepcionUseCase: VerificarRecepcionUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun verificarRecepcion(ruta: Ruta, litrosCaudalimetro: Double, estadoVerificacion: String, observacion: String?) =
        verificarRecepcionUseCase(ruta, litrosCaudalimetro, estadoVerificacion, observacion)
}
