package pe.edu.upeu.milkflow.ui.util

import java.text.Normalizer

/**
 * Normaliza para comparaciones/búsqueda: minúsculas y sin tildes ni diéresis.
 * "José Ñañez" y "jose nanez" pasan a coincidir.
 */
fun String.sinAcentos(): String =
    Normalizer.normalize(this, Normalizer.Form.NFD)
        .replace(Regex("\\p{Mn}+"), "")
        .lowercase()
        .trim()

/** ¿[this] contiene [consulta] ignorando acentos y mayúsculas? Consulta vacía = sí. */
fun String.coincideSinAcentos(consulta: String): Boolean {
    val q = consulta.sinAcentos()
    return q.isBlank() || this.sinAcentos().contains(q)
}
