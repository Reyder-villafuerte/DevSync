package pe.edu.upeu.milkflow.ui.util

import kotlin.time.Instant
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.number
import kotlinx.datetime.toLocalDateTime

/** Zona horaria fija: coincide con el corte de día del backend y el ciclo de pago. */
val ZonaLima: TimeZone = TimeZone.of("America/Lima")

fun litros(valor: Double): String = "%.2f L".format(valor)

fun soles(valor: Double): String = "S/ %.2f".format(valor)

fun Instant.fechaHoraLima(): String {
    val ldt = toLocalDateTime(ZonaLima)
    return "%02d/%02d/%04d %02d:%02d".format(
        ldt.day, ldt.month.number, ldt.year, ldt.hour, ldt.minute,
    )
}

fun Instant.horaLima(): String {
    val ldt = toLocalDateTime(ZonaLima)
    return "%02d:%02d".format(ldt.hour, ldt.minute)
}

fun LocalDate.formatoCorto(): String = "%02d/%02d/%04d".format(day, month.number, year)
