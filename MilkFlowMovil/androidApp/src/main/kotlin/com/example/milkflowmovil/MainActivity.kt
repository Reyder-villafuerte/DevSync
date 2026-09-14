package com.example.milkflowmovil

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.example.milkflowmovil.datos.local.ContextoAndroid

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)

        // La base local del módulo compartido necesita saber dónde escribir
        // antes de que se componga la primera pantalla.
        ContextoAndroid.inicializar(applicationContext)

        setContent {
            App()
        }
    }
}
