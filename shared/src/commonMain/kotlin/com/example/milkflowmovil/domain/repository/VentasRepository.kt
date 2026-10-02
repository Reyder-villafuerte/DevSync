package com.example.milkflowmovil.domain.repository

import com.example.milkflowmovil.core.ErrorApp

/** Contrato de la caja: ventas y cierre del día. Devuelven un error de regla o null si todo salió bien. */
interface VentasRepository {
    fun registrarVenta(
        clienteId: Long?,
        nuevoNombre: String?,
        nuevoApellido: String?,
        nuevoDni: String?,
        nuevoTipo: String?,
        moldes: Int,
        formaPago: String,
    ): ErrorApp?

    fun cerrarCaja(notas: String?): ErrorApp?
}
