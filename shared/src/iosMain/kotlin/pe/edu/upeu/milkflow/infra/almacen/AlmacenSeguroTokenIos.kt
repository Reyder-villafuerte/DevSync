package pe.edu.upeu.milkflow.infra.almacen

import kotlinx.cinterop.BetaInteropApi
import kotlinx.cinterop.CValue
import kotlinx.cinterop.ExperimentalForeignApi
import kotlinx.cinterop.alloc
import kotlinx.cinterop.memScoped
import kotlinx.cinterop.ptr
import kotlinx.cinterop.value
import platform.CoreFoundation.CFDictionaryAddValue
import platform.CoreFoundation.CFDictionaryCreateMutable
import platform.CoreFoundation.CFMutableDictionaryRef
import platform.CoreFoundation.CFTypeRefVar
import platform.CoreFoundation.kCFBooleanTrue
import platform.CoreFoundation.kCFTypeDictionaryKeyCallBacks
import platform.CoreFoundation.kCFTypeDictionaryValueCallBacks
import platform.Foundation.CFBridgingRelease
import platform.Foundation.CFBridgingRetain
import platform.Foundation.NSData
import platform.Foundation.NSString
import platform.Foundation.NSUTF8StringEncoding
import platform.Foundation.create
import platform.Foundation.dataUsingEncoding
import platform.Security.SecItemAdd
import platform.Security.SecItemCopyMatching
import platform.Security.SecItemDelete
import platform.Security.errSecSuccess
import platform.Security.kSecAttrAccessible
import platform.Security.kSecAttrAccessibleWhenUnlockedThisDeviceOnly
import platform.Security.kSecAttrAccount
import platform.Security.kSecAttrService
import platform.Security.kSecClass
import platform.Security.kSecClassGenericPassword
import platform.Security.kSecMatchLimit
import platform.Security.kSecMatchLimitOne
import platform.Security.kSecReturnData
import platform.Security.kSecValueData

/**
 * Token de Sanctum en el Keychain de iOS (kSecClassGenericPassword), accesible
 * solo con el dispositivo desbloqueado y sin salir de este equipo.
 *
 * Nota: cinterop de Security no verificable en este entorno; probar en Xcode.
 * Si se prefiere, `iOSApp.swift` puede implementar el Keychain en Swift e
 * inyectar un [AlmacenSeguroToken] alternativo por Koin.
 */
@OptIn(ExperimentalForeignApi::class, BetaInteropApi::class)
class AlmacenSeguroTokenIos(
    private val servicio: String = "pe.edu.upeu.milkflow",
    private val cuenta: String = "token_sanctum",
) : AlmacenSeguroToken {

    override suspend fun guardar(token: String) {
        borrar()
        val datos = ((token as NSString).dataUsingEncoding(NSUTF8StringEncoding)) ?: return
        val q = base()
        CFDictionaryAddValue(q, kSecValueData, CFBridgingRetain(datos))
        CFDictionaryAddValue(q, kSecAttrAccessible, kSecAttrAccessibleWhenUnlockedThisDeviceOnly)
        SecItemAdd(q, null)
    }

    override suspend fun leer(): String? = memScoped {
        val q = base()
        CFDictionaryAddValue(q, kSecReturnData, kCFBooleanTrue)
        CFDictionaryAddValue(q, kSecMatchLimit, kSecMatchLimitOne)
        val salida = alloc<CFTypeRefVar>()
        if (SecItemCopyMatching(q, salida.ptr) != errSecSuccess) return@memScoped null
        val data = CFBridgingRelease(salida.value) as? NSData ?: return@memScoped null
        NSString.create(data, NSUTF8StringEncoding) as String?
    }

    override suspend fun borrar() {
        SecItemDelete(base())
    }

    /** Diccionario CF con class + service + account (identifica el ítem). */
    private fun base(): CFMutableDictionaryRef? {
        val d = CFDictionaryCreateMutable(null, 0, kCFTypeDictionaryKeyCallBacks.ptr, kCFTypeDictionaryValueCallBacks.ptr)
        CFDictionaryAddValue(d, kSecClass, kSecClassGenericPassword)
        CFDictionaryAddValue(d, kSecAttrService, CFBridgingRetain(servicio as NSString))
        CFDictionaryAddValue(d, kSecAttrAccount, CFBridgingRetain(cuenta as NSString))
        return d
    }
}
