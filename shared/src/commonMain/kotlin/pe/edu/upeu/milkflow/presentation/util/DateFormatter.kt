package pe.edu.upeu.milkflow.presentation.util

import kotlin.time.Instant
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime

fun formatFecha(
    instant: Instant?,
    timeZone: TimeZone = TimeZone.currentSystemDefault(),
): String {
    if (instant == null || instant.toEpochMilliseconds() == 0L) {
        return "Fecha no disponible"
    }
    
    val dateTime = instant.toLocalDateTime(timeZone)
    val year = dateTime.year
    val month = dateTime.monthNumber.toString().padStart(2, '0')
    val day = dateTime.day.toString().padStart(2, '0')
    val hour = dateTime.hour.toString().padStart(2, '0')
    val minute = dateTime.minute.toString().padStart(2, '0')
    
    return "$day/$month/$year · $hour:$minute"
}
