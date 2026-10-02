package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.ErrorApp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.data.local.nuevoUuid
import com.example.milkflowmovil.domain.model.CierreCaja
import com.example.milkflowmovil.domain.model.Stock
import com.example.milkflowmovil.domain.model.Venta
import com.example.milkflowmovil.domain.repository.VentasRepository
import com.example.milkflowmovil.domain.rules.Reglas
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/** Caja: venta de moldes de queso y cierre del día. */
class VentasRepositoryImpl(
    private val cola: ColaOperaciones,
) : VentasRepository {

    private val base get() = cola.base
    private val yo get() = cola.yo

    override fun registrarVenta(
        clienteId: Long?,
        nuevoNombre: String?,
        nuevoApellido: String?,
        nuevoDni: String?,
        nuevoTipo: String?,
        moldes: Int,
        formaPago: String,
    ): ErrorApp? {
        val usuario = yo ?: return ErrorApp.SesionVencida

        if (base.actual.stockQueso < moldes) {
            return ErrorApp.Regla("Stock insuficiente de quesos. Disponibles: ${base.actual.stockQueso} moldes.")
        }

        if (clienteId == null && nuevoApellido.isNullOrBlank()) {
            return ErrorApp.Regla("Elige un cliente registrado o escribe el apellido del cliente nuevo.")
        }

        val cliente = base.actual.cliente(clienteId)
        val precio = Reglas.precioQueso(cliente, moldes, base.actual.tarifaVigente)
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val venta = Venta(
                id = idNuevo,
                clientUuid = uuid,
                recibo = "POR SINCRONIZAR",
                clienteId = clienteId ?: 0,
                vendedorId = usuario.id,
                moldes = moldes,
                precioUnitario = precio,
                total = precio * moldes,
                formaPago = formaPago,
                vendidoEn = Fechas.ahoraIso(),
                pendiente = true,
            )

            estado.copy(
                ventas = estado.ventas + venta,
                stocks = ajustarStock(estado, Stock.QUESO, -moldes.toDouble()),
            )
        }

        cola.encolar(
            uuid, "registrar_venta",
            buildJsonObject {
                clienteId?.let { put("customer_id", it) }
                nuevoNombre?.takeIf { it.isNotBlank() }?.let { put("new_first_name", it) }
                nuevoApellido?.takeIf { it.isNotBlank() }?.let { put("new_last_name", it) }
                nuevoDni?.takeIf { it.isNotBlank() }?.let { put("new_dni_ruc", it) }
                nuevoTipo?.takeIf { it.isNotBlank() }?.let { put("new_type", it) }
                put("cheese_molds_quantity", moldes)
                put("payment_method", formaPago)
            },
            "Venta de $moldes moldes de queso",
        )

        return null
    }

    override fun cerrarCaja(notas: String?): ErrorApp? {
        val usuario = yo ?: return ErrorApp.SesionVencida
        val hoy = Fechas.hoy()

        val ventasDelDia = base.actual.ventas.filter {
            it.vendidoEn.take(10) == hoy && it.cierreId == null
        }

        if (ventasDelDia.isEmpty()) {
            return ErrorApp.Regla("No hay ventas activas pendientes de cierre para el día de hoy.")
        }

        val uuid = nuevoUuid()
        val idCierre = base.reservarIdTemporal()

        base.actualizar { estado ->
            val cierre = CierreCaja(
                id = idCierre,
                clientUuid = uuid,
                date = hoy,
                cerradoPor = usuario.id,
                efectivo = ventasDelDia.filterNot { it.esDescuentoLeche }.sumOf { it.total },
                descuentoLeche = ventasDelDia.filter { it.esDescuentoLeche }.sumOf { it.total },
                total = ventasDelDia.sumOf { it.total },
                moldes = ventasDelDia.sumOf { it.moldes },
                transacciones = ventasDelDia.size,
                notes = notas,
                cerradoEn = Fechas.ahoraIso(),
                pendiente = true,
            )

            estado.copy(
                cierresCaja = estado.cierresCaja + cierre,
                ventas = estado.ventas.map {
                    if (ventasDelDia.any { venta -> venta.id == it.id }) it.copy(cierreId = idCierre) else it
                },
            )
        }

        cola.encolar(
            uuid, "cerrar_caja",
            buildJsonObject {
                put("fecha", hoy)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
            },
            "Cierre de caja del ${Fechas.corta(hoy)}",
        )

        return null
    }
}
