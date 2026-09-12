package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.StateFlow
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SesionRepository
import pe.edu.upeu.milkflow.domain.sync.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.sync.SyncManager
import pe.edu.upeu.milkflow.domain.vo.Dni

/**
 * El acopiador abre la ruta del día. Sin red.
 *
 * @param unaJornadaPorDia regla de negocio "una jornada por acopiador y día".
 *   APAGADA en la fase de pruebas para poder repetir el ciclo abrir/cerrar el
 *   mismo día. Al volver a activarla hay que restaurar además el índice único
 *   de `rutas_acopio` (migración 2026_09_10_000000) y poner
 *   `sync.jornada_unica_por_dia => true` en el backend: si el cliente permite
 *   lo que el servidor prohíbe, la jornada extra se rechaza en la subida y
 *   arrastra sus recolecciones por clave foránea.
 */
class IniciarJornadaUseCase(
    private val jornadas: JornadaRepository,
    private val unaJornadaPorDia: Boolean = false,
) {
    suspend operator fun invoke(acopiadorId: String, rutaId: String): Resultado<JornadaRuta> {
        jornadas.jornadaActiva(acopiadorId)?.let { return Resultado.Exito(it) } // idempotente

        if (unaJornadaPorDia && jornadas.jornadaDeHoy() != null) {
            return Resultado.Fallo(
                ErrorApp.ReglaNegocio(
                    "jornada_del_dia_ya_cerrada",
                    "La ruta de hoy ya fue cerrada. No se puede abrir una segunda ruta el mismo día.",
                ),
            )
        }
        return jornadas.iniciar(acopiadorId, rutaId)
    }
}

/**
 * Login. Es la ÚNICA operación de escritura de usuario que requiere red.
 *
 * Al terminar dispara la PRIMERA bajada. Es imprescindible: el cierre de sesión
 * vacía la caché local (`limpiarCacheLocal`) y el disparo automático por
 * conectividad ya ocurrió antes de existir el token, así que sin esto el
 * dispositivo se queda sin rutas, sin zonas y sin padrón hasta que alguien
 * pulse "Sincronizar" a mano. Corre en el alcance del SyncManager, no en el de
 * la pantalla de login (que se destruye al re-enrutar al grafo del rol).
 */
class IniciarSesionUseCase(
    private val sesion: SesionRepository,
    private val trasIniciarSesion: () -> Unit = {},
) {
    suspend operator fun invoke(dniTexto: String, password: String, idDispositivo: String): Resultado<SesionActiva> =
        when (val dni = Dni.de(dniTexto)) {
            is Resultado.Fallo -> dni
            is Resultado.Exito -> sesion.iniciarSesion(dni.valor, password, idDispositivo)
                .also { if (it is Resultado.Exito) trasIniciarSesion() }
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
