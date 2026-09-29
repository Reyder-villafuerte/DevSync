package com.example.milkflowmovil.datos.local

import platform.Foundation.NSDocumentDirectory
import platform.Foundation.NSFileManager
import platform.Foundation.NSSearchPathForDirectoriesInDomains
import platform.Foundation.NSString
import platform.Foundation.NSURL
import platform.Foundation.NSUUID
import platform.Foundation.NSUTF8StringEncoding
import platform.Foundation.NSUserDomainMask
import platform.Foundation.stringWithContentsOfFile
import platform.Foundation.writeToFile

private class AlmacenArchivosIos : AlmacenArchivos {

    private fun ruta(nombre: String): String {
        val documentos = NSSearchPathForDirectoriesInDomains(
            NSDocumentDirectory, NSUserDomainMask, true
        ).first() as String

        val carpeta = "$documentos/milkflow"
        if (!NSFileManager.defaultManager.fileExistsAtPath(carpeta)) {
            NSFileManager.defaultManager.createDirectoryAtURL(
                NSURL.fileURLWithPath(carpeta), true, null, null
            )
        }
        return "$carpeta/$nombre"
    }

    override fun leer(nombre: String): String? =
        NSString.stringWithContentsOfFile(ruta(nombre), NSUTF8StringEncoding, null)

    override fun escribir(nombre: String, contenido: String) {
        (contenido as NSString).writeToFile(ruta(nombre), true, NSUTF8StringEncoding, null)
    }

    override fun borrar(nombre: String) {
        NSFileManager.defaultManager.removeItemAtPath(ruta(nombre), null)
    }
}

actual fun almacenPlataforma(): AlmacenArchivos = AlmacenArchivosIos()

actual fun identificadorDispositivo(): String {
    val almacen = almacenPlataforma()
    almacen.leer(ARCHIVO_DISPOSITIVO)?.takeIf { it.isNotBlank() }?.let { return it.trim() }

    val nuevo = "ios-" + NSUUID().UUIDString().take(18)
    almacen.escribir(ARCHIVO_DISPOSITIVO, nuevo)
    return nuevo
}

actual fun nuevoUuid(): String = NSUUID().UUIDString().lowercase()

private const val ARCHIVO_DISPOSITIVO = "dispositivo.txt"
