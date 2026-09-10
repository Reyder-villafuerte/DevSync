package pe.edu.upeu.milkflow.core

import kotlin.time.Instant

/**
 * Fuente de tiempo inyectable. La domain la usa en lugar de Clock.System para
 * que los use cases y la lógica de sincronización sean deterministas en tests
 * (evaluación RN, cálculo de backoff, sellos updated_at locales).
 */
fun interface Reloj {
    fun ahora(): Instant
}
