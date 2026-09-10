package pe.edu.upeu.milkflow.sync

import android.content.Context
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import org.koin.core.component.KoinComponent
import org.koin.core.component.inject
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.sync.SyncManager
import java.util.concurrent.TimeUnit

/**
 * Sincronización en segundo plano cada 15 minutos con red disponible.
 * Reintenta (con backoff de WorkManager) si el ciclo falla por transporte.
 */
class SyncWorker(
    contexto: Context,
    parametros: WorkerParameters,
) : CoroutineWorker(contexto, parametros), KoinComponent {

    private val syncManager: SyncManager by inject()

    override suspend fun doWork(): Result = when (syncManager.sincronizar()) {
        is Resultado.Exito -> Result.success()
        is Resultado.Fallo -> Result.retry()
    }

    companion object {
        private const val NOMBRE = "milkflow-sync-periodico"

        fun programar(contexto: Context) {
            val restricciones = Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()

            val solicitud = PeriodicWorkRequestBuilder<SyncWorker>(15, TimeUnit.MINUTES)
                .setConstraints(restricciones)
                .build()

            WorkManager.getInstance(contexto).enqueueUniquePeriodicWork(
                NOMBRE,
                ExistingPeriodicWorkPolicy.KEEP,
                solicitud,
            )
        }
    }
}
