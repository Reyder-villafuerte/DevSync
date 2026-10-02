package com.example.milkflowmovil.domain.repository

import com.example.milkflowmovil.domain.model.Ruta

/** Contrato de la verificación en planta con caudalímetro. */
interface PlantaRepository {
    fun verificarRecepcion(ruta: Ruta, litrosCaudalimetro: Double, estadoVerificacion: String, observacion: String?)
}
