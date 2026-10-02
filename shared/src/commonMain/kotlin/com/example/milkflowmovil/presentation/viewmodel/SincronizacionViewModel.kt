package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.domain.model.EstadoSync
import com.example.milkflowmovil.domain.usecase.CambiarServidorUseCase
import com.example.milkflowmovil.domain.usecase.DescartarRechazadaUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.ObservarSincronizacionUseCase
import com.example.milkflowmovil.domain.usecase.SincronizarAhoraUseCase
import kotlinx.coroutines.flow.StateFlow

/** Pantalla de sincronización e indicador de la barra superior. */
class SincronizacionViewModel(
    observarEstado: ObservarEstadoUseCase,
    observarSincronizacion: ObservarSincronizacionUseCase,
    private val sincronizarAhora: SincronizarAhoraUseCase,
    private val cambiarServidorUseCase: CambiarServidorUseCase,
    private val descartarRechazadaUseCase: DescartarRechazadaUseCase,
) : MilkFlowViewModel(observarEstado) {

    val estadoSync: StateFlow<EstadoSync> = observarSincronizacion()

    fun sincronizarEnSegundoPlano() = sincronizarAhora()

    fun cambiarServidor(url: String) = cambiarServidorUseCase(url)

    fun descartarRechazada(uuid: String) = descartarRechazadaUseCase(uuid)
}
