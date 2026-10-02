package com.example.milkflowmovil.domain.repository

import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.domain.model.EstadoApp
import kotlinx.coroutines.flow.StateFlow

/**
 * Contrato del estado de la app y de la sesión.
 *
 * El ViewModel y los casos de uso solo conocen esta interfaz: no saben si los
 * datos vienen del teléfono, de la API o de ambos.
 */
interface SesionRepository {
    /** Estado completo de la app; toda pantalla lo observa. */
    val estado: StateFlow<EstadoApp>

    /** Foto del estado en este instante. */
    val actual: EstadoApp

    /** Carga los datos guardados en el teléfono y arranca la sincronización automática. */
    suspend fun iniciar()

    suspend fun iniciarSesion(usuario: String, password: String): Resultado<Unit>

    suspend fun cerrarSesion()

    fun cambiarServidor(url: String)
}
