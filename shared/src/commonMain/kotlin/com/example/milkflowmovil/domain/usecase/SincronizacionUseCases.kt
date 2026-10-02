package com.example.milkflowmovil.domain.usecase

import com.example.milkflowmovil.domain.model.EstadoSync
import com.example.milkflowmovil.domain.repository.SincronizacionRepository
import kotlinx.coroutines.flow.StateFlow

class ObservarSincronizacionUseCase(private val repositorio: SincronizacionRepository) {
    operator fun invoke(): StateFlow<EstadoSync> = repositorio.estadoSync
}

/** Sube la cola y baja los cambios sin bloquear la pantalla. */
class SincronizarAhoraUseCase(private val repositorio: SincronizacionRepository) {
    operator fun invoke() = repositorio.sincronizarEnSegundoPlano()
}

/** Quita de la lista una operación que el servidor rechazó. */
class DescartarRechazadaUseCase(private val repositorio: SincronizacionRepository) {
    operator fun invoke(uuid: String) = repositorio.descartarRechazada(uuid)
}
