package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.usecase.AutorizarPagoUseCase
import com.example.milkflowmovil.domain.usecase.AutorizarTodosLosPagosUseCase
import com.example.milkflowmovil.domain.usecase.EntregarSobreUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase

/** Autorización de pagos y sobres en ruta. */
class PagosViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val autorizarPagoUseCase: AutorizarPagoUseCase,
    private val autorizarTodosLosPagosUseCase: AutorizarTodosLosPagosUseCase,
    private val entregarSobreUseCase: EntregarSobreUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun autorizarPago(productorId: Long) = autorizarPagoUseCase(productorId)

    fun autorizarTodosLosPagos() = autorizarTodosLosPagosUseCase()

    fun entregarSobre(productorId: Long) = entregarSobreUseCase(productorId)
}
