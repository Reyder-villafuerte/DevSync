package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.domain.repository.PagosRepository

class AutorizarPagoUseCase(private val repositorio: PagosRepository) {
    operator fun invoke(productorId: Long) = repositorio.autorizarPago(productorId)
}

class AutorizarTodosLosPagosUseCase(private val repositorio: PagosRepository) {
    operator fun invoke() = repositorio.autorizarTodosLosPagos()
}

class EntregarSobreUseCase(private val repositorio: PagosRepository) {
    operator fun invoke(productorId: Long) = repositorio.entregarSobre(productorId)
}
