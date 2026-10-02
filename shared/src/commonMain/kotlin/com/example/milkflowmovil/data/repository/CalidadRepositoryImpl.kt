package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.data.local.nuevoUuid
import com.example.milkflowmovil.domain.model.Analisis
import com.example.milkflowmovil.domain.model.VisitaTecnica
import com.example.milkflowmovil.domain.repository.CalidadRepository
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/** Calidad: análisis Lactoscan y cierre de visitas técnicas. */
class CalidadRepositoryImpl(
    private val cola: ColaOperaciones,
) : CalidadRepository {

    private val base get() = cola.base
    private val yo get() = cola.yo

    override fun registrarAnalisis(
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
    ) {
        val usuario = yo ?: return
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                analisis = estado.analisis + Analisis(
                    id = idNuevo,
                    clientUuid = uuid,
                    productorId = productorId,
                    inspectorId = usuario.id,
                    fecha = Fechas.hoy(),
                    grasa = grasa,
                    solidos = solidos,
                    density = densidad,
                    proteina = proteina,
                    agua = agua,
                    temperature = temperatura,
                    acidez = acidez,
                    verdict = veredicto,
                    notes = notas,
                    pendiente = true,
                )
            )
        }

        cola.encolar(
            uuid, "registrar_analisis",
            buildJsonObject {
                put("producer_id", productorId)
                put("analysis_date", Fechas.hoy())
                grasa?.let { put("fat_percentage", it) }
                solidos?.let { put("snf_percentage", it) }
                densidad?.let { put("density", it) }
                proteina?.let { put("protein_percentage", it) }
                agua?.let { put("water_addition_percentage", it) }
                temperatura?.let { put("temperature", it) }
                acidez?.let { put("ph_or_acidity", it) }
                put("verdict", veredicto)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
                if (agendarVisita) put("schedule_visit", true)
                fechaVisita?.let { put("scheduled_date", it) }
                motivoVisita?.takeIf { it.isNotBlank() }?.let { put("visit_reason", it) }
            },
            "Análisis Lactoscan de ${base.actual.usuario(productorId)?.name ?: "productor"}",
        )
    }

    override fun completarVisita(visita: VisitaTecnica, informe: String) {
        if (visita.id <= 0) return
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(visitas = estado.visitas.map {
                if (it.id == visita.id) it.copy(status = "realizada", informe = informe, pendiente = true) else it
            })
        }

        cola.encolar(
            uuid, "completar_visita",
            buildJsonObject {
                put("visit_id", visita.id)
                put("resolution_report", informe)
            },
            "Cierre de visita técnica",
        )
    }
}
