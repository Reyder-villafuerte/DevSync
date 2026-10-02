package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.domain.model.Ruta
import com.example.milkflowmovil.domain.repository.AcopioRepository

class AbrirRutaDeHoyUseCase(private val repositorio: AcopioRepository) {
    operator fun invoke(): Ruta? = repositorio.abrirRutaDeHoy()
}

class RegistrarEntregaUseCase(private val repositorio: AcopioRepository) {
    operator fun invoke(productorId: Long, litros: Double, notas: String?) =
        repositorio.registrarEntrega(productorId, litros, notas)
}

class CerrarRutaUseCase(private val repositorio: AcopioRepository) {
    operator fun invoke() = repositorio.cerrarRuta()
}
