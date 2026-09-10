package pe.edu.upeu.milkflow.domain.vo
import kotlin.jvm.JvmInline

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado

/**
 * Documento Nacional de Identidad peruano: 8 dígitos. Coherente con el CHECK
 * `dni ~ '^[0-9]{8}$'` del backend.
 */
@JvmInline
value class Dni private constructor(val valor: String) {

    override fun toString(): String = valor

    companion object {
        private val PATRON = Regex("^[0-9]{8}$")

        fun de(entrada: String): Resultado<Dni> {
            val limpio = entrada.trim()
            return if (PATRON.matches(limpio)) {
                Resultado.Exito(Dni(limpio))
            } else {
                Resultado.Fallo(ErrorApp.Validacion("DNI", "debe tener exactamente 8 dígitos"))
            }
        }

        /** Para reconstruir desde datos ya validados (BD local, DTO del servidor). */
        fun confiar(valor: String): Dni = Dni(valor)
    }
}
