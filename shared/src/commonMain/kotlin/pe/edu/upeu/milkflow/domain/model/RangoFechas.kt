package pe.edu.upeu.milkflow.domain.model

import kotlin.time.Instant
import pe.edu.upeu.milkflow.domain.RangoFechasInvalidoException

/** Intervalo semiabierto: incluye [inicio] y excluye [finExclusivo]. */
data class RangoFechas(
    val inicio: Instant,
    val finExclusivo: Instant,
) {
    init {
        if (inicio >= finExclusivo) {
            throw RangoFechasInvalidoException()
        }
    }

    operator fun contains(fechaHora: Instant): Boolean =
        fechaHora >= inicio && fechaHora < finExclusivo
}
