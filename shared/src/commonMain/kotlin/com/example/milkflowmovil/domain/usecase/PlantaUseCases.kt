package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.domain.model.Ruta
import com.example.milkflowmovil.domain.repository.PlantaRepository

/** Registra los litros medidos por el caudalímetro: es lo único que entra al stock. */
class VerificarRecepcionUseCase(private val repositorio: PlantaRepository) {
    operator fun invoke(ruta: Ruta, litrosCaudalimetro: Double, estadoVerificacion: String, observacion: String?) =
        repositorio.verificarRecepcion(ruta, litrosCaudalimetro, estadoVerificacion, observacion)
}
