package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.data.local.nuevoUuid
import com.example.milkflowmovil.domain.model.Recepcion
import com.example.milkflowmovil.domain.model.Ruta
import com.example.milkflowmovil.domain.model.Stock
import com.example.milkflowmovil.domain.repository.PlantaRepository
import com.example.milkflowmovil.domain.rules.Reglas
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/** Planta: verificación de la ruta con el caudalímetro. Solo lo medido entra al stock. */
class PlantaRepositoryImpl(
    private val cola: ColaOperaciones,
) : PlantaRepository {

    private val base get() = cola.base
    private val yo get() = cola.yo

    override fun verificarRecepcion(ruta: Ruta, litrosCaudalimetro: Double, estadoVerificacion: String, observacion: String?) {
        val usuario = yo ?: return
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val previa = estado.recepciones.firstOrNull { it.rutaId == ruta.id }

            val recepcion = (previa ?: Recepcion(id = idNuevo, rutaId = ruta.id)).copy(
                clientUuid = uuid,
                verificadorId = usuario.id,
                litrosDeclarados = ruta.litrosTotales,
                litrosCaudalimetro = litrosCaudalimetro,
                diferencia = Reglas.merma(ruta.litrosTotales, litrosCaudalimetro),
                estado = estadoVerificacion,
                observation = observacion,
                verificadoEn = Fechas.ahoraIso(),
                pendiente = true,
            )

            val litrosPrevios = previa?.litrosCaudalimetro ?: 0.0

            estado.copy(
                recepciones = estado.recepciones.filterNot { it.id == recepcion.id } + recepcion,
                rutas = estado.rutas.map { if (it.id == ruta.id) it.copy(status = "verificada") else it },
                // Al stock entra solo lo del caudalímetro; si se corrige, el ajuste es el delta.
                stocks = ajustarStock(estado, Stock.LECHE, litrosCaudalimetro - litrosPrevios),
            )
        }

        cola.encolar(
            uuid, "verificar_recepcion",
            buildJsonObject {
                if (ruta.id > 0) put("ruta_id", ruta.id)
                ruta.clientUuid?.let { put("ruta_client_uuid", it) }
                put("flowmeter_liters", litrosCaudalimetro)
                put("verification_status", estadoVerificacion)
                observacion?.takeIf { it.isNotBlank() }?.let { put("observation", it) }
            },
            "Caudalímetro de la ruta del ${Fechas.corta(ruta.date)}: $litrosCaudalimetro L",
        )
    }
}
