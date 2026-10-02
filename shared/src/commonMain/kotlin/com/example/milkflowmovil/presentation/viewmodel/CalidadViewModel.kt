package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.model.VisitaTecnica
import com.example.milkflowmovil.domain.usecase.CompletarVisitaUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarAnalisisUseCase

/** Lactoscan y citas técnicas. */
class CalidadViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val registrarAnalisisUseCase: RegistrarAnalisisUseCase,
    private val completarVisitaUseCase: CompletarVisitaUseCase,
) : MilkFlowViewModel(observarEstado) {

    fun registrarAnalisis(
        productorId: Long,
        grasa: Double?,
        solidos: Double?,
        densidad: Double?,
        proteina: Double?,
        agua: Double?,
        temperatura: Double?,
        acidez: Double?,
        veredicto: String,
        notas: String?,
        agendarVisita: Boolean,
        fechaVisita: String?,
        motivoVisita: String?,
    ) = registrarAnalisisUseCase(
        productorId, grasa, solidos, densidad, proteina, agua, temperatura, acidez,
        veredicto, notas, agendarVisita, fechaVisita, motivoVisita,
    )

    fun completarVisita(visita: VisitaTecnica, informe: String) = completarVisitaUseCase(visita, informe)
}
