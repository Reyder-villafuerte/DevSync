package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase

/** Panel del día: solo lectura de indicadores. */
class PanelViewModel(observarEstado: ObservarEstadoUseCase) : MilkFlowViewModel(observarEstado)
