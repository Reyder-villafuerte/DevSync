package com.example.milkflowmovil.domain.repository

/** Contrato de administración: egresos, tarifas y avisos. */
interface AdministracionRepository {
    fun registrarEgreso(
        categoria: String,
        descripcion: String,
        monto: Double,
        fecha: String,
        beneficiario: String?,
        personalId: Long?,
        formaPago: String,
        comprobante: String?,
    )

    fun actualizarTarifas(
        temporada: String,
        lecheBase: Double,
        aguaLeve: Double,
        aguaGrave: Double,
        quesoProveedor: Double,
        quesoMayorista: Double,
        quesoLocal: Double,
        notas: String?,
    )

    fun publicarAviso(titulo: String, mensaje: String, desde: String, hasta: String, rolDestino: String?)
}
