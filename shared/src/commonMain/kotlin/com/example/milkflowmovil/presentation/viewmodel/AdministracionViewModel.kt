package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.usecase.ActualizarTarifasUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.PublicarAvisoUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarEgresoUseCase

/** Tarifas, avisos y flujo de caja. */
class AdministracionViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val registrarEgresoUseCase: RegistrarEgresoUseCase,
    private val actualizarTarifasUseCase: ActualizarTarifasUseCase,
    private val publicarAvisoUseCase: PublicarAvisoUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun registrarEgreso(
        categoria: String,
        descripcion: String,
        monto: Double,
        fecha: String,
        beneficiario: String?,
        personalId: Long?,
        formaPago: String,
        comprobante: String?,
    ) = registrarEgresoUseCase(categoria, descripcion, monto, fecha, beneficiario, personalId, formaPago, comprobante)

    fun actualizarTarifas(
        temporada: String,
        lecheBase: Double,
        aguaLeve: Double,
        aguaGrave: Double,
        quesoProveedor: Double,
        quesoMayorista: Double,
        quesoLocal: Double,
        notas: String?,
    ) = actualizarTarifasUseCase(temporada, lecheBase, aguaLeve, aguaGrave, quesoProveedor, quesoMayorista, quesoLocal, notas)

    fun publicarAviso(titulo: String, mensaje: String, desde: String, hasta: String, rolDestino: String?) =
        publicarAvisoUseCase(titulo, mensaje, desde, hasta, rolDestino)
}
