package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.StateFlow
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SesionRepository
import pe.edu.upeu.milkflow.domain.sync.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.sync.SyncManager
import pe.edu.upeu.milkflow.domain.vo.Dni

/** El acopiador abre la ruta del día. Sin red. */
class IniciarJornadaUseCase(private val jornadas: JornadaRepository) {
    suspend operator fun invoke(acopiadorId: String, rutaId: String): Resultado<JornadaRuta> {
        jornadas.jornadaActiva(acopiadorId)?.let { return Resultado.Exito(it) } // idempotente
        return jornadas.iniciar(acopiadorId, rutaId)
    }
}

/** Login. Es la ÚNICA operación de escritura de usuario que requiere red. */
class IniciarSesionUseCase(private val sesion: SesionRepository) {
    suspend operator fun invoke(dniTexto: String, password: String, idDispositivo: String): Resultado<SesionActiva> =
        when (val dni = Dni.de(dniTexto)) {
            is Resultado.Fallo -> dni
            is Resultado.Exito -> sesion.iniciarSesion(dni.valor, password, idDispositivo)
        }
}

/** Dispara una sincronización manual (pull-to-refresh, botón "reintentar"). */
class SincronizarAhoraUseCase(private val syncManager: SyncManager) {
    suspend operator fun invoke(): Resultado<Unit> = syncManager.sincronizar()
}

/** Estado observable para el indicador del encabezado. */
class ObtenerEstadoSincronizacionUseCase(private val syncManager: SyncManager) {
    operator fun invoke(): StateFlow<EstadoSincronizacion> = syncManager.estado
}
