package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.data.local.nuevoUuid
import com.example.milkflowmovil.domain.model.Aviso
import com.example.milkflowmovil.domain.model.Egreso
import com.example.milkflowmovil.domain.repository.AdministracionRepository
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/** Administración: egresos, tarifas de temporada y avisos de inicio. */
class AdministracionRepositoryImpl(
    private val cola: ColaOperaciones,
) : AdministracionRepository {

    private val base get() = cola.base
    private val yo get() = cola.yo

    override fun registrarEgreso(
        categoria: String,
        descripcion: String,
        monto: Double,
        fecha: String,
        beneficiario: String?,
        personalId: Long?,
        formaPago: String,
        comprobante: String?,
    ) {
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                egresos = estado.egresos + Egreso(
                    id = idNuevo,
                    clientUuid = uuid,
                    category = categoria,
                    description = descripcion,
                    amount = monto,
                    fecha = fecha,
                    personalId = personalId,
                    beneficiario = beneficiario,
                    formaPago = formaPago,
                    comprobante = comprobante,
                    pendiente = true,
                )
            )
        }

        cola.encolar(
            uuid, "registrar_egreso",
            buildJsonObject {
                put("category", categoria)
                put("description", descripcion)
                put("amount", monto)
                put("expense_date", fecha)
                personalId?.let { put("user_id", it) }
                beneficiario?.takeIf { it.isNotBlank() }?.let { put("beneficiary_name", it) }
                put("payment_method", formaPago)
                comprobante?.takeIf { it.isNotBlank() }?.let { put("receipt_number", it) }
            },
            "Egreso de ${monto} por $descripcion",
        )
    }

    override fun actualizarTarifas(
        temporada: String,
        lecheBase: Double,
        aguaLeve: Double,
        aguaGrave: Double,
        quesoProveedor: Double,
        quesoMayorista: Double,
        quesoLocal: Double,
        notas: String?,
    ) {
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val nueva = estado.tarifaVigente.copy(
                id = idNuevo,
                temporada = temporada,
                lecheBase = lecheBase,
                lecheAguaLeve = aguaLeve,
                lecheAguaGrave = aguaGrave,
                quesoProveedor = quesoProveedor,
                quesoMayorista = quesoMayorista,
                quesoLocal = quesoLocal,
                activa = true,
                notes = notas,
            )

            estado.copy(tarifas = estado.tarifas.map { it.copy(activa = false) } + nueva)
        }

        cola.encolar(
            uuid, "actualizar_tarifas",
            buildJsonObject {
                put("season_name", temporada)
                put("price_milk_base", lecheBase)
                put("price_milk_water_penalty_low", aguaLeve)
                put("price_milk_water_penalty_high", aguaGrave)
                put("price_cheese_provider", quesoProveedor)
                put("price_cheese_wholesale", quesoMayorista)
                put("price_cheese_local", quesoLocal)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
            },
            "Nuevas tarifas: $temporada",
        )
    }

    override fun publicarAviso(titulo: String, mensaje: String, desde: String, hasta: String, rolDestino: String?) {
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                avisos = estado.avisos + Aviso(
                    id = idNuevo,
                    clientUuid = uuid,
                    title = titulo,
                    message = mensaje,
                    desde = desde,
                    hasta = hasta,
                    rolDestino = rolDestino,
                    pendiente = true,
                )
            )
        }

        cola.encolar(
            uuid, "publicar_aviso",
            buildJsonObject {
                put("title", titulo)
                put("message", mensaje)
                put("start_date", desde)
                put("end_date", hasta)
                rolDestino?.takeIf { it.isNotBlank() }?.let { put("target_role", it) }
            },
            "Aviso: $titulo",
        )
    }
}
