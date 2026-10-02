package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.domain.model.EstadoApp
import com.example.milkflowmovil.domain.repository.SesionRepository
import kotlinx.coroutines.flow.StateFlow

/*
 * Casos de uso de la sesión. Cada clase hace una sola cosa y solo conoce la
 * interfaz del repositorio, nunca su implementación.
 */

/** Estado de la app que observan todas las pantallas. */
class ObservarEstadoUseCase(private val repositorio: SesionRepository) {
    operator fun invoke(): StateFlow<EstadoApp> = repositorio.estado
}

/** Carga lo guardado en el teléfono y arranca la sincronización automática. */
class IniciarAppUseCase(private val repositorio: SesionRepository) {
    suspend operator fun invoke() = repositorio.iniciar()
}

class IniciarSesionUseCase(private val repositorio: SesionRepository) {
    suspend operator fun invoke(usuario: String, password: String): Resultado<Unit> =
        repositorio.iniciarSesion(usuario, password)
}

class CerrarSesionUseCase(private val repositorio: SesionRepository) {
    suspend operator fun invoke() = repositorio.cerrarSesion()
}

/** Cambia la dirección del servidor de la planta (la IP no es fija). */
class CambiarServidorUseCase(private val repositorio: SesionRepository) {
    operator fun invoke(url: String) = repositorio.cambiarServidor(url)
}
