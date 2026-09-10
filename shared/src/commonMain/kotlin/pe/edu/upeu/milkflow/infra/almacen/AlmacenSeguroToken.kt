package pe.edu.upeu.milkflow.infra.almacen

/**
 * Almacenamiento cifrado del token de Sanctum.
 *  - Android: EncryptedSharedPreferences (clave en el Keystore del dispositivo).
 *  - iOS: Keychain (kSecClassGenericPassword, accesible solo con el dispositivo
 *    desbloqueado).
 *
 * El token NUNCA se guarda en la BD SQLDelight ni en preferencias en claro.
 */
interface AlmacenSeguroToken {
    suspend fun guardar(token: String)
    suspend fun leer(): String?
    suspend fun borrar()
}
