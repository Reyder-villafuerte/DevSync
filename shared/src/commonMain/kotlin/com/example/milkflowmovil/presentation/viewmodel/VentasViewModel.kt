package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.core.ErrorApp
import com.example.milkflowmovil.domain.usecase.CerrarCajaUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarVentaUseCase

/** Ventas del día, nueva venta y recibos. */
class VentasViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val registrarVentaUseCase: RegistrarVentaUseCase,
    private val cerrarCajaUseCase: CerrarCajaUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun registrarVenta(
        clienteId: Long?,
        nuevoNombre: String?,
        nuevoApellido: String?,
        nuevoDni: String?,
        nuevoTipo: String?,
        moldes: Int,
        formaPago: String,
    ): ErrorApp? = registrarVentaUseCase(clienteId, nuevoNombre, nuevoApellido, nuevoDni, nuevoTipo, moldes, formaPago)

    fun cerrarCaja(notas: String?): ErrorApp? = cerrarCajaUseCase(notas)
}
