package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.domain.repository.AdministracionRepository

class RegistrarEgresoUseCase(private val repositorio: AdministracionRepository) {
    operator fun invoke(
        categoria: String,
        descripcion: String,
        monto: Double,
        fecha: String,
        beneficiario: String?,
        personalId: Long?,
        formaPago: String,
        comprobante: String?,
    ) = repositorio.registrarEgreso(categoria, descripcion, monto, fecha, beneficiario, personalId, formaPago, comprobante)
}

class ActualizarTarifasUseCase(private val repositorio: AdministracionRepository) {
    operator fun invoke(
        temporada: String,
        lecheBase: Double,
        aguaLeve: Double,
        aguaGrave: Double,
        quesoProveedor: Double,
        quesoMayorista: Double,
        quesoLocal: Double,
        notas: String?,
    ) = repositorio.actualizarTarifas(
        temporada, lecheBase, aguaLeve, aguaGrave, quesoProveedor, quesoMayorista, quesoLocal, notas,
    )
}

class PublicarAvisoUseCase(private val repositorio: AdministracionRepository) {
    operator fun invoke(titulo: String, mensaje: String, desde: String, hasta: String, rolDestino: String?) =
        repositorio.publicarAviso(titulo, mensaje, desde, hasta, rolDestino)
}
