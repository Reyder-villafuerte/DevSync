package pe.edu.upeu.milkflow.domain.vo
import kotlin.jvm.JvmInline

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import kotlin.math.roundToLong

/**
 * Precio de compra de leche por litro, en soles. Guardado en milésimas de sol
 * (Long) porque el backend usa numeric(10,4) — S/ 1.70 normal, S/ 0.60–0.70
 * degradada (RN-05).
 */
@JvmInline
value class TarifaPorLitro private constructor(val milesimas: Long) : Comparable<TarifaPorLitro> {

    val valor: Double get() = milesimas / 10_000.0

    override fun compareTo(other: TarifaPorLitro): Int = milesimas.compareTo(other.milesimas)
    override fun toString(): String = "S/ ${valor}"

    companion object {
        fun de(soles: Double): Resultado<TarifaPorLitro> = when {
            soles.isNaN() || soles <= 0.0 -> Resultado.Fallo(ErrorApp.Validacion("Tarifa", "debe ser mayor a cero"))
            else -> Resultado.Exito(TarifaPorLitro((soles * 10_000).roundToLong()))
        }

        fun confiar(soles: Double): TarifaPorLitro = TarifaPorLitro((soles * 10_000).roundToLong())
    }
}

/**
 * Importe monetario en soles, en céntimos (Long). Para montos de liquidación,
 * subtotales de venta, descuentos.
 */
@JvmInline
value class Dinero private constructor(val centimos: Long) : Comparable<Dinero> {
    val valor: Double get() = centimos / 100.0
    operator fun plus(o: Dinero) = Dinero(centimos + o.centimos)
    operator fun minus(o: Dinero) = Dinero(centimos - o.centimos)
    override fun compareTo(other: Dinero): Int = centimos.compareTo(other.centimos)
    override fun toString(): String = "S/ ${valor}"

    companion object {
        val CERO = Dinero(0)
        fun confiar(soles: Double): Dinero = Dinero((soles * 100).roundToLong())
    }
}
