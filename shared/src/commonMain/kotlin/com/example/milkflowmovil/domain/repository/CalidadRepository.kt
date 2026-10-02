package com.example.milkflowmovil.domain.repository

import com.example.milkflowmovil.domain.model.VisitaTecnica

/** Contrato de calidad: análisis Lactoscan y visitas técnicas. */
interface CalidadRepository {
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
    )

    fun completarVisita(visita: VisitaTecnica, informe: String)
}
