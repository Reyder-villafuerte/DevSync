package com.example.milkflowmovil.domain.repository

import com.example.milkflowmovil.domain.model.Ruta

/** Contrato del acopio en ruta (4:30 AM). */
interface AcopioRepository {
    /** Ruta de hoy del acopiador que inició sesión, si ya existe. */
    fun rutaDeHoy(): Ruta?

    /** Devuelve la ruta de hoy; si no existe, abre una provisional sin señal. */
    fun abrirRutaDeHoy(): Ruta?

    fun registrarEntrega(productorId: Long, litros: Double, notas: String?)

    fun cerrarRuta()
}
