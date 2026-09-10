package pe.edu.upeu.milkflow.sync

import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.sync.SyncManager

/**
 * Puente para BGTaskScheduler. El registro real (BGTaskScheduler.register) y el
 * `submit` de un BGAppRefreshTaskRequest con `earliestBeginDate` a +15 min viven
 * en Swift (AppDelegate), porque requieren Info.plist
 * (BGTaskSchedulerPermittedIdentifiers) y el ciclo de vida de la app.
 *
 * Swift, al recibir la tarea, llama a [ejecutar] y completa el
 * BGTask con `setTaskCompleted(success:)` según el resultado.
 */
object ProgramadorSincronizacionIos {

    /** Identificador que debe declararse en Info.plist y usarse en Swift. */
    const val IDENTIFICADOR_TAREA = "pe.edu.upeu.milkflow.sync"

    /** Intervalo objetivo (el sistema iOS decide el momento exacto). */
    const val INTERVALO_SEGUNDOS: Long = 15 * 60

    /**
     * Ejecuta un ciclo de sincronización. Devuelve true si terminó bien, para
     * que Swift llame `task.setTaskCompleted(success: true/false)`.
     */
    suspend fun ejecutar(syncManager: SyncManager): Boolean =
        syncManager.sincronizar() is Resultado.Exito
}
