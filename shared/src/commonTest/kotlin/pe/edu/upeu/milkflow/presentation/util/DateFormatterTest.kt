package pe.edu.upeu.milkflow.presentation.util

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.time.Instant
import kotlinx.datetime.TimeZone

class DateFormatterTest {
    @Test
    fun `problema muestra fecha local legible`() {
        assertEquals(
            "07/09/2026 · 14:31",
            formatFecha(
                instant = Instant.parse("2026-09-07T19:31:00Z"),
                timeZone = TimeZone.of("America/Lima"),
            ),
        )
    }

    @Test
    fun `epoch cero se muestra como fecha no disponible`() {
        assertEquals(
            "Fecha no disponible",
            formatFecha(
                instant = Instant.fromEpochMilliseconds(0),
                timeZone = TimeZone.of("America/Lima"),
            ),
        )
    }
}
