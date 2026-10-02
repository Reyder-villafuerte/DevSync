package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.data.local.nuevoUuid
import com.example.milkflowmovil.domain.repository.PagosRepository
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/** Pagos: autorización en oficina y entrega del sobre en ruta. */
class PagosRepositoryImpl(
    private val cola: ColaOperaciones,
) : PagosRepository {

    private val base get() = cola.base
    private val yo get() = cola.yo

    override fun autorizarPago(productorId: Long) {
        val uuid = nuevoUuid()

        cola.encolar(
            uuid, "autorizar_pago",
            buildJsonObject { put("producer_id", productorId) },
            "Autorización de pago a ${base.actual.usuario(productorId)?.name ?: "productor"}",
        )
    }

    override fun autorizarTodosLosPagos() {
        cola.encolar(
            nuevoUuid(), "autorizar_pago",
            buildJsonObject { put("todos", true) },
            "Autorización masiva de pagos del ciclo",
        )
    }

    override fun entregarSobre(productorId: Long) {
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(liquidaciones = estado.liquidaciones.map {
                if (it.productorId == productorId && it.status == "autorizado") {
                    it.copy(status = "pagado", pagadoEn = Fechas.ahoraIso(), pagadoPor = yo?.id, pendiente = true)
                } else {
                    it
                }
            })
        }

        cola.encolar(
            uuid, "entregar_sobre",
            buildJsonObject { put("producer_id", productorId) },
            "Entrega de sobre a ${base.actual.usuario(productorId)?.name ?: "productor"}",
        )
    }
}
