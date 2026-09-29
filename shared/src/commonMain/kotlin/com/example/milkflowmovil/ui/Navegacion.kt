package com.example.milkflowmovil.ui

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import com.example.milkflowmovil.dominio.Pantalla
import com.example.milkflowmovil.dominio.Rol

/**
 * Navegación propia, deliberadamente mínima: una pila de pantallas con un
 * argumento opcional. El rol decide a dónde se puede ir; una pantalla fuera
 * de su menú simplemente no se abre.
 */
class Navegador(private val rol: Rol) {
    private var pila by mutableStateOf(listOf(rol.inicio))

    var argumento by mutableStateOf<Long?>(null)
        private set

    val actual: Pantalla get() = pila.last()

    val puedeVolver: Boolean get() = pila.size > 1

    fun ir(destino: Pantalla, argumento: Long? = null) {
        if (!rol.puedeVer(destino)) return
        this.argumento = argumento
        pila = if (destino == rol.inicio) listOf(destino) else pila + destino
    }

    /** Desde el menú lateral: se reinicia la pila, no se apila sobre lo anterior. */
    fun irDesdeMenu(destino: Pantalla) {
        if (!rol.puedeVer(destino)) return
        argumento = null
        pila = listOf(destino)
    }

    fun volver() {
        if (puedeVolver) {
            pila = pila.dropLast(1)
            argumento = null
        }
    }
}
