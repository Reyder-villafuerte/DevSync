package com.example.milkflowmovil.data.remote.dto

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
