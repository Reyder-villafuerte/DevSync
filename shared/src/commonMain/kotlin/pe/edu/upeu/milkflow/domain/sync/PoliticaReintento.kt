package pe.edu.upeu.milkflow.domain.sync

import kotlin.math.min
import kotlin.math.pow
import kotlin.random.Random
import kotlin.time.Duration
import kotlin.time.Duration.Companion.milliseconds
import kotlin.time.Duration.Companion.minutes
import kotlin.time.Duration.Companion.seconds

/**
 * Backoff exponencial con jitter y tope de intentos.
 *
 * - `demoraPara(intento)`: cuánto esperar antes del reintento nº `intento`
 *   (1 = primer reintento). base * 2^(intento-1), acotado a `demoraMaxima`,
 *   con jitter ±20% para no sincronizar todos los dispositivos a la vez tras
 *   una caída del backend ("thundering herd").
 * - `agotado(intentos)`: si se superó `maxIntentos`, la operación se marca como
 *   CONFLICTO y se expone en la UI en vez de reintentar en silencio.
 */
class PoliticaReintento(
    private val base: Duration = 2.seconds,
    private val demoraMaxima: Duration = 5.minutes,
    val maxIntentos: Int = 6,
    private val random: Random = Random.Default,
) {
    fun demoraPara(intento: Int): Duration {
        val n = (intento - 1).coerceAtLeast(0)
        val exp = base.inWholeMilliseconds * 2.0.pow(n)
        val acotado = min(exp, demoraMaxima.inWholeMilliseconds.toDouble())
        val jitter = 1.0 + random.nextDouble(-0.2, 0.2)
        return (acotado * jitter).toLong().coerceAtLeast(0L).milliseconds
    }

    fun agotado(intentos: Int): Boolean = intentos >= maxIntentos
}
