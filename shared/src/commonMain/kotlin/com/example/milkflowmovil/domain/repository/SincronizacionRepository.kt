package com.example.milkflowmovil.domain.repository

import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.domain.model.EstadoSync
import kotlinx.coroutines.flow.StateFlow

/** Contrato de la sincronización offline-first: cola de subida y bajada por cursor. */
interface SincronizacionRepository {
    val estadoSync: StateFlow<EstadoSync>

    suspend fun sincronizar(): Resultado<Unit>

    fun sincronizarEnSegundoPlano()

    fun descartarRechazada(uuid: String)
}
