package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.usecase.AbrirRutaDeHoyUseCase
import com.example.milkflowmovil.domain.usecase.CerrarRutaUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarEntregaUseCase

/** Acopio 4:30 AM e historial de rutas. */
class AcopioViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val abrirRutaDeHoyUseCase: AbrirRutaDeHoyUseCase,
    private val registrarEntregaUseCase: RegistrarEntregaUseCase,
    private val cerrarRutaUseCase: CerrarRutaUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun abrirRutaDeHoy() {
        abrirRutaDeHoyUseCase()
    }

    fun registrarEntrega(productorId: Long, litros: Double, notas: String?) =
        registrarEntregaUseCase(productorId, litros, notas)

    fun cerrarRuta() = cerrarRutaUseCase()
}
