package com.example.milkflowmovil.presentation.viewmodel

import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.domain.model.EstadoApp
import com.example.milkflowmovil.domain.usecase.CambiarServidorUseCase
import com.example.milkflowmovil.domain.usecase.CerrarSesionUseCase
import com.example.milkflowmovil.domain.usecase.IniciarAppUseCase
import com.example.milkflowmovil.domain.usecase.IniciarSesionUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase

/** Arranque de la app, login y cierre de sesión. */
class SesionViewModel(
    observarEstado: ObservarEstadoUseCase,
    private val iniciarApp: IniciarAppUseCase,
    private val iniciarSesionUseCase: IniciarSesionUseCase,
    private val cerrarSesionUseCase: CerrarSesionUseCase,
    private val cambiarServidorUseCase: CambiarServidorUseCase,
) : MilkFlowViewModel(observarEstado) {

    /** Foto del estado en este instante (valor inicial de los formularios). */
    val actual: EstadoApp get() = uiState.value

    suspend fun iniciar() = iniciarApp()

    suspend fun iniciarSesion(usuario: String, password: String): Resultado<Unit> =
        iniciarSesionUseCase(usuario, password)

    suspend fun cerrarSesion() = cerrarSesionUseCase()

    fun cambiarServidor(url: String) = cambiarServidorUseCase(url)
}
