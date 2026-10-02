package com.example.milkflowmovil.presentation.viewmodel

import androidx.lifecycle.ViewModel
import com.example.milkflowmovil.domain.model.EstadoApp
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import kotlinx.coroutines.flow.StateFlow

/**
 * Base de todos los ViewModel de la app.
 *
 * Expone `uiState`: el estado que la pantalla dibuja con collectAsState().
 * La pantalla nunca ve DTO ni JSON, solo modelos de dominio (diapositiva 13).
 */
abstract class MilkFlowViewModel(observarEstado: ObservarEstadoUseCase) : ViewModel() {
    val uiState: StateFlow<EstadoApp> = observarEstado()
}
