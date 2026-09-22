package com.example.milkflowmovil

import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import com.example.milkflowmovil.datos.local.ContextoAndroid

class MainActivity : ComponentActivity() {

    private val pedirRedLocal = registerForActivityResult(ActivityResultContracts.RequestPermission()) {
        // Si lo niegan, la app sigue trabajando contra la base local y lo dirá
        // como «sin conexión». No hay nada que reintentar aquí.
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)

        // La base local del módulo compartido necesita saber dónde escribir
        // antes de que se componga la primera pantalla.
        ContextoAndroid.inicializar(applicationContext)

        asegurarAccesoALaRedLocal()

        setContent {
            App()
        }
    }

    /**
     * El servidor de la planta está en la red local, no en internet.
     *
     * Desde Android 16, alcanzar una IP privada (192.168.x, 10.x, 172.16-31.x)
     * necesita su propio permiso además de INTERNET. Sin él la conexión no
     * falla: se queda colgada hasta que vence el tiempo de espera, y la app
     * dice «sin conexión» con el wifi conectado. Es el peor error posible
     * porque no se ve por ningún lado.
     */
    private fun asegurarAccesoALaRedLocal() {
        if (Build.VERSION.SDK_INT < 36) {
            return
        }

        // El nombre va como texto a propósito: compilando contra un SDK más
        // viejo la constante no existe, y ahí el permiso no hace falta.
        val permiso = "android.permission.ACCESS_LOCAL_NETWORK"

        if (checkSelfPermission(permiso) != PackageManager.PERMISSION_GRANTED) {
            pedirRedLocal.launch(permiso)
        }
    }
}
