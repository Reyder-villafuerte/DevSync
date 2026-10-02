package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.data.sync.Sincronizador
import com.example.milkflowmovil.domain.model.EstadoSync
import com.example.milkflowmovil.domain.repository.SincronizacionRepository
import kotlinx.coroutines.flow.StateFlow

/** Sincronización: expone el estado del motor y permite forzar un ciclo o limpiar rechazos. */
class SincronizacionRepositoryImpl(
    private val sincronizador: Sincronizador,
    private val cola: ColaOperaciones,
) : SincronizacionRepository {

    override val estadoSync: StateFlow<EstadoSync> = sincronizador.estado

    override suspend fun sincronizar(): Resultado<Unit> = sincronizador.sincronizar()

    override fun sincronizarEnSegundoPlano() = cola.sincronizarEnSegundoPlano()

    override fun descartarRechazada(uuid: String) {
        cola.base.actualizar { estado ->
            estado.copy(rechazadas = estado.rechazadas.filterNot { it.clientUuid == uuid })
        }
    }
}
