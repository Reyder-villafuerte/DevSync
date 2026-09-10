package pe.edu.upeu.milkflow.core

/**
 * Resultado tipado de una operación que puede fallar de forma esperada.
 *
 * Se usa un tipo propio (y no kotlin.Result) porque queremos un error de
 * dominio cerrado ([ErrorApp]) que la UI pueda exhaustivamente diferenciar,
 * y porque kotlin.Result no compone bien en firmas de interfaz multiplataforma.
 */
sealed interface Resultado<out T> {
    data class Exito<T>(val valor: T) : Resultado<T>
    data class Fallo(val error: ErrorApp) : Resultado<Nothing>
}

inline fun <T, R> Resultado<T>.map(transform: (T) -> R): Resultado<R> = when (this) {
    is Resultado.Exito -> Resultado.Exito(transform(valor))
    is Resultado.Fallo -> this
}

inline fun <T, R> Resultado<T>.flatMap(transform: (T) -> Resultado<R>): Resultado<R> = when (this) {
    is Resultado.Exito -> transform(valor)
    is Resultado.Fallo -> this
}

inline fun <T> Resultado<T>.onExito(bloque: (T) -> Unit): Resultado<T> {
    if (this is Resultado.Exito) bloque(valor)
    return this
}

inline fun <T> Resultado<T>.onFallo(bloque: (ErrorApp) -> Unit): Resultado<T> {
    if (this is Resultado.Fallo) bloque(error)
    return this
}

fun <T> Resultado<T>.valorOrNull(): T? = (this as? Resultado.Exito)?.valor

fun <T> exito(valor: T): Resultado<T> = Resultado.Exito(valor)
fun fallo(error: ErrorApp): Resultado<Nothing> = Resultado.Fallo(error)
