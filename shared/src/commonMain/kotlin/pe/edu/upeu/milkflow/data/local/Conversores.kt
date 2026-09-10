package pe.edu.upeu.milkflow.data.local

import kotlin.time.Instant
import kotlinx.datetime.LocalDate

/** BD local: los instantes se guardan como epoch millis (INTEGER). */
internal fun Instant.aMillis(): Long = toEpochMilliseconds()
internal fun Long.aInstant(): Instant = Instant.fromEpochMilliseconds(this)
internal fun Long?.aInstantOrNull(): Instant? = this?.let(Instant::fromEpochMilliseconds)

/** Fechas de negocio: TEXT ISO yyyy-MM-dd. */
internal fun LocalDate.aTexto(): String = toString()
internal fun String.aLocalDate(): LocalDate = LocalDate.parse(this.take(10))

internal fun Boolean.aLong(): Long = if (this) 1L else 0L
internal fun Long.aBoolean(): Boolean = this != 0L
