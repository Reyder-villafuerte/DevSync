package pe.edu.upeu.milkflow

import android.app.Application
import org.koin.android.ext.koin.androidContext
import org.koin.android.ext.koin.androidLogger
import pe.edu.upeu.milkflow.config.ConfiguracionServidor
import pe.edu.upeu.milkflow.core.NivelLogHttp
import pe.edu.upeu.milkflow.di.ConfiguracionMilkFlow
import pe.edu.upeu.milkflow.di.iniciarKoin
import pe.edu.upeu.milkflow.di.moduloAndroid
import pe.edu.upeu.milkflow.sync.SyncWorker

class MilkFlowApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        // Guarda el stack trace de cualquier crash para mostrarlo en pantalla al reabrir.
        CazadorDeCrashes.instalar(this)

        iniciarKoin(
            ConfiguracionMilkFlow(
                // URL editable en la pantalla de login (persistida en SharedPreferences).
                urlBase = ConfiguracionServidor.leer(this),
                nivelLogHttp = NivelLogHttp.BASICO,
            ),
        ) {
            androidLogger()
            androidContext(this@MilkFlowApplication)
            // Módulo de la capa de presentación (ViewModels de androidApp).
            modules(moduloAndroid)
        }

        // Sincronización periódica cada 15 minutos con red.
        SyncWorker.programar(this)
    }
}
