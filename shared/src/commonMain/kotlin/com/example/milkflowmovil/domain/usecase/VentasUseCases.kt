package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.core.ErrorApp
import com.example.milkflowmovil.domain.repository.VentasRepository

class RegistrarVentaUseCase(private val repositorio: VentasRepository) {
    operator fun invoke(
        clienteId: Long?,
        nuevoNombre: String?,
        nuevoApellido: String?,
        nuevoDni: String?,
        nuevoTipo: String?,
        moldes: Int,
        formaPago: String,
    ): ErrorApp? = repositorio.registrarVenta(
        clienteId, nuevoNombre, nuevoApellido, nuevoDni, nuevoTipo, moldes, formaPago,
    )
}

class CerrarCajaUseCase(private val repositorio: VentasRepository) {
    operator fun invoke(notas: String?): ErrorApp? = repositorio.cerrarCaja(notas)
}
