package com.example.milkflowmovil.core

import kotlinx.serialization.KSerializer
import kotlinx.serialization.descriptors.PrimitiveKind
import kotlinx.serialization.descriptors.PrimitiveSerialDescriptor
import kotlinx.serialization.descriptors.SerialDescriptor
import kotlinx.serialization.encoding.Decoder
import kotlinx.serialization.encoding.Encoder
import kotlinx.serialization.json.JsonDecoder
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.jsonPrimitive

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

/**
 * Los decimales de MySQL llegan como texto ("18.50") y los de SQLite como
 * número (18.5). Estos serializadores aceptan ambas formas para que el móvil
 * no se caiga según el motor de base de datos del servidor.
 */
object DobleFlexible : KSerializer<Double> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("DobleFlexible", PrimitiveKind.DOUBLE)

    override fun deserialize(decoder: Decoder): Double {
        val json = decoder as? JsonDecoder ?: return decoder.decodeDouble()
        val primitivo = json.decodeJsonElement().jsonPrimitive
        return primitivo.content.toDoubleOrNull() ?: 0.0
    }

    override fun serialize(encoder: Encoder, value: Double) = encoder.encodeDouble(value)
}

object EnteroFlexible : KSerializer<Int> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("EnteroFlexible", PrimitiveKind.INT)

    override fun deserialize(decoder: Decoder): Int {
        val json = decoder as? JsonDecoder ?: return decoder.decodeInt()
        val primitivo = json.decodeJsonElement().jsonPrimitive
        return primitivo.content.toDoubleOrNull()?.toInt() ?: 0
    }

    override fun serialize(encoder: Encoder, value: Int) = encoder.encodeInt(value)
}

object BooleanoFlexible : KSerializer<Boolean> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("BooleanoFlexible", PrimitiveKind.BOOLEAN)

    override fun deserialize(decoder: Decoder): Boolean {
        val json = decoder as? JsonDecoder ?: return decoder.decodeBoolean()
        val contenido = (json.decodeJsonElement() as? JsonPrimitive)?.content ?: return false
        return contenido == "1" || contenido.equals("true", ignoreCase = true)
    }

    override fun serialize(encoder: Encoder, value: Boolean) = encoder.encodeBoolean(value)
}
