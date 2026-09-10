package pe.edu.upeu.milkflow.domain.vo
import kotlin.jvm.JvmInline

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import kotlin.math.roundToLong

/**
 * Volumen de leche en litros. Se guarda internamente en centilitros (Long) para
 * no arrastrar error de punto flotante al sumar cientos de recolecciones en un
 * reporte. El backend usa numeric(8,2); aquí 2 decimales = centilitros.
 */
@JvmInline
value class Litros private constructor(val centilitros: Long) : Comparable<Litros> {

    val valor: Double get() = centilitros / 100.0

    operator fun plus(otro: Litros) = Litros(centilitros + otro.centilitros)
    operator fun minus(otro: Litros) = Litros(centilitros - otro.centilitros)
    override fun compareTo(other: Litros): Int = centilitros.compareTo(other.centilitros)
    override fun toString(): String = valor.toString()

    companion object {
        val CERO = Litros(0)

        fun de(valor: Double): Resultado<Litros> = when {
            valor.isNaN() || valor.isInfinite() -> Resultado.Fallo(ErrorApp.Validacion("Litros", "valor inválido"))
            valor <= 0.0 -> Resultado.Fallo(ErrorApp.Validacion("Litros", "debe ser mayor a cero"))
            valor > 100_000.0 -> Resultado.Fallo(ErrorApp.Validacion("Litros", "fuera de rango"))
            else -> Resultado.Exito(Litros((valor * 100).roundToLong()))
        }

        fun confiar(valor: Double): Litros = Litros((valor * 100).roundToLong())
        fun sumar(items: Iterable<Litros>): Litros = Litros(items.sumOf { it.centilitros })
    }
}
