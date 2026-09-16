package com.example.milkflowmovil.datos.local

import android.content.Context
import java.io.File
import java.util.UUID

/**
 * La app Android entrega aquí su contexto al arrancar; el módulo compartido
 * solo necesita saber en qué carpeta escribir.
 */
object ContextoAndroid {
    private var directorio: File? = null

    fun inicializar(contexto: Context) {
        directorio = File(contexto.filesDir, "milkflow").apply { mkdirs() }
    }

    fun carpeta(): File = directorio
        ?: error("Llama a ContextoAndroid.inicializar(context) antes de usar la base local.")
}

private class AlmacenArchivosAndroid : AlmacenArchivos {
    override fun leer(nombre: String): String? {
        val archivo = File(ContextoAndroid.carpeta(), nombre)
        return if (archivo.exists()) archivo.readText() else null
    }

    override fun escribir(nombre: String, contenido: String) {
        val carpeta = ContextoAndroid.carpeta()
        // Se escribe en un temporal y se renombra: si el teléfono se apaga a
        // mitad del guardado, el archivo bueno anterior sigue intacto.
        val temporal = File(carpeta, "$nombre.tmp")
        temporal.writeText(contenido)
        val destino = File(carpeta, nombre)
        if (destino.exists()) destino.delete()
        temporal.renameTo(destino)
    }

    override fun borrar(nombre: String) {
        File(ContextoAndroid.carpeta(), nombre).delete()
    }
}

actual fun almacenPlataforma(): AlmacenArchivos = AlmacenArchivosAndroid()

actual fun identificadorDispositivo(): String {
    val almacen = almacenPlataforma()
    almacen.leer(ARCHIVO_DISPOSITIVO)?.takeIf { it.isNotBlank() }?.let { return it.trim() }

    val nuevo = "android-" + UUID.randomUUID().toString().take(18)
    almacen.escribir(ARCHIVO_DISPOSITIVO, nuevo)
    return nuevo
}

actual fun nuevoUuid(): String = UUID.randomUUID().toString()

private const val ARCHIVO_DISPOSITIVO = "dispositivo.txt"
