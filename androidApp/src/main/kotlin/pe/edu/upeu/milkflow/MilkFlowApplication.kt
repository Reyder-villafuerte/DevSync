package pe.edu.upeu.milkflow

import android.app.Application
import pe.edu.upeu.milkflow.di.initKoinAndroid

class MilkFlowApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        
        // Iniciar Koin con el validador para admin123
        initKoinAndroid(this) { _, clave ->
            clave == "admin123"
        }
    }
}
