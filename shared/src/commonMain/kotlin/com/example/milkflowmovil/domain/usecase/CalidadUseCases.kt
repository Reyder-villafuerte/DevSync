package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.domain.model.VisitaTecnica
import com.example.milkflowmovil.domain.repository.CalidadRepository

class RegistrarAnalisisUseCase(private val repositorio: CalidadRepository) {
    operator fun invoke(
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
    ) = repositorio.registrarAnalisis(
        productorId, grasa, solidos, densidad, proteina, agua, temperatura, acidez,
        veredicto, notas, agendarVisita, fechaVisita, motivoVisita,
    )
}

class CompletarVisitaUseCase(private val repositorio: CalidadRepository) {
    operator fun invoke(visita: VisitaTecnica, informe: String) = repositorio.completarVisita(visita, informe)
}
