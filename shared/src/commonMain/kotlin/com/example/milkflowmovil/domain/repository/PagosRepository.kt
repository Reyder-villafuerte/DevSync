package com.example.milkflowmovil.domain.repository

/** Contrato de pagos: autorización en oficina y entrega de sobres en ruta. */
interface PagosRepository {
    fun autorizarPago(productorId: Long)

    fun autorizarTodosLosPagos()

    fun entregarSobre(productorId: Long)
}
