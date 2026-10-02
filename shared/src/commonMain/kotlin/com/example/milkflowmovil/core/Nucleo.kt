package com.example.milkflowmovil.core

/**
 * Errores que la interfaz sabe explicar al usuario.
 *
 * `SinRed` no es un fallo: en Huata lo normal es trabajar sin señal. La app
 * guarda todo en la cola y lo sube cuando vuelve la cobertura.
 */
sealed class ErrorApp(val mensaje: String) {
    data object SinRed : ErrorApp("Sin conexión. El trabajo se guardó en el teléfono y se enviará al volver la señal.")
    data object Credenciales : ErrorApp("DNI o contraseña incorrectos.")
    data object CuentaInactiva : ErrorApp("Tu cuenta está desactivada. Comunícate con administración.")
    data object SesionVencida : ErrorApp("Tu sesión caducó. Vuelve a iniciar sesión cuando tengas señal.")
    class Regla(mensaje: String) : ErrorApp(mensaje)
    class Servidor(mensaje: String) : ErrorApp(mensaje)
}

/** Resultado de una operación que puede fallar de forma esperable. */
sealed class Resultado<out T> {
    data class Exito<T>(val valor: T) : Resultado<T>()
    data class Fallo(val error: ErrorApp) : Resultado<Nothing>()

    val exitoso: Boolean get() = this is Exito

    fun valorONulo(): T? = (this as? Exito)?.valor
    fun errorONulo(): ErrorApp? = (this as? Fallo)?.error
}
