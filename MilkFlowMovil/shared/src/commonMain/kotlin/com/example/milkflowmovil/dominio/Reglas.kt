package com.example.milkflowmovil.dominio

import com.example.milkflowmovil.core.Fechas
import kotlin.math.abs
import kotlin.math.max
import kotlin.math.round

/**
 * Reglas de negocio de Huata calculadas en el teléfono.
 *
 * Están aquí porque el acopiador y el pagador trabajan sin señal: el móvil
 * tiene que poder mostrar el precio, el descuento y el sobre sin preguntarle
 * nada al servidor. El servidor vuelve a calcularlo al recibir la operación y
 * su resultado manda; esto es la vista previa fiel de ese cálculo.
 */
object Reglas {

    // ------------------------------------------------------------- TARIFAS

    /**
     * Precio del molde de queso: proveedor S/ 18, mayorista o 10+ moldes S/ 19,
     * público local S/ 20 (valores vigentes de la tarifa de temporada).
     */
    fun precioQueso(cliente: Cliente?, cantidad: Int, tarifa: Tarifa): Double = when {
        cliente != null && (cliente.type == "proveedor" || cliente.usuarioVinculadoId != null) -> tarifa.quesoProveedor
        cliente != null && (cliente.type == "mayorista" || cliente.mayoristaAprobado) -> tarifa.quesoMayorista
        cantidad >= 10 -> tarifa.quesoMayorista
        else -> tarifa.quesoLocal
    }

    /** Resultado de evaluar la calidad de la leche de un productor en un periodo. */
    data class PrecioLeche(
        val precio: Double,
        val penalidad: Penalidad,
        val porcentajeAgua: Double,
        val motivo: String,
    )

    enum class Penalidad { NINGUNA, LEVE, GRAVE }

    /**
     * Precio por litro según el peor análisis del periodo:
     * agua > 5% es penalidad grave (y riesgo de expulsión); cualquier rastro de
     * agua hasta 5% es penalidad leve; sin agua, precio base.
     */
    fun precioLeche(analisisDelPeriodo: List<Analisis>, tarifa: Tarifa): PrecioLeche {
        val peor = analisisDelPeriodo.maxByOrNull { it.agua ?: 0.0 }
        val agua = peor?.agua ?: 0.0

        return when {
            agua > 5.0 -> PrecioLeche(
                tarifa.lecheAguaGrave, Penalidad.GRAVE, agua,
                "Agua adicionada alta ($agua%). Penalidad y advertencia de expulsión.",
            )

            agua > 0.0 -> PrecioLeche(
                tarifa.lecheAguaLeve, Penalidad.LEVE, agua,
                "Agua detectada ($agua% ≤ 5%). Precio reducido por adulteración leve.",
            )

            else -> PrecioLeche(tarifa.lecheBase, Penalidad.NINGUNA, 0.0, "Leche conforme, sin agua detectada.")
        }
    }

    // -------------------------------------------------------- LIQUIDACIONES

    /** Sobre semanal de un productor tal como lo arma administración. */
    data class Sobre(
        val productorId: Long,
        val desde: String,
        val hasta: String,
        val litros: Double,
        val precioBase: Double,
        val precioEfectivo: Double,
        val bruto: Double,
        val descuentoQueso: Double,
        val penalidadAgua: Double,
        val penalidadPorLitro: Double,
        val totalDeducciones: Double,
        val neto: Double,
        val hayAdulteracion: Boolean,
        val porcentajeAgua: Double,
        val autorizado: Boolean,
        val pagado: Boolean,
    )

    /**
     * Inicio del ciclo abierto: el día siguiente a la última liquidación pagada.
     * Si nunca se le pagó, los últimos siete días.
     */
    fun inicioCiclo(liquidaciones: List<Liquidacion>, hoy: String = Fechas.hoy()): String {
        val ultimaPagada = liquidaciones
            .filter { it.status == "pagado" }
            .maxByOrNull { it.hasta }

        val inicio = ultimaPagada?.let { Fechas.sumarDias(it.hasta, 1) } ?: Fechas.hace(6)
        return if (inicio > hoy) hoy else inicio
    }

    /**
     * Arma el sobre del ciclo abierto.
     *
     * Regla de Huata: si se detecta agua CUALQUIER día del ciclo, la diferencia
     * de precio se descuenta sobre TODOS los litros de la semana, no solo los
     * de ese día.
     */
    fun calcularSobre(
        productorId: Long,
        entregas: List<Entrega>,
        rutas: Map<Long, Ruta>,
        analisis: List<Analisis>,
        descuentos: List<Descuento>,
        liquidaciones: List<Liquidacion>,
        tarifa: Tarifa,
        hoy: String = Fechas.hoy(),
    ): Sobre {
        val desde = inicioCiclo(liquidaciones, hoy)

        val entregasDelCiclo = entregas.filter { entrega ->
            val fecha = rutas[entrega.rutaId]?.date ?: return@filter false
            fecha in desde..hoy
        }

        val litros = entregasDelCiclo.sumOf { it.liters }

        val analisisDelCiclo = analisis.filter { it.fecha in desde..hoy }
        val precio = precioLeche(analisisDelCiclo, tarifa)

        // Se redondea a céntimos antes de multiplicar: 1.40 - 1.20 da
        // 0.19999… en coma flotante y el productor vería un descuento raro.
        val penalidadPorLitro = if (precio.penalidad == Penalidad.NINGUNA) 0.0
        else redondear(max(0.0, tarifa.lecheBase - precio.precio))

        val penalidadAgua = redondear(penalidadPorLitro * litros)

        val descuentoQueso = redondear(
            descuentos.filter { it.status == "pendiente" && it.esQueso }.sumOf { it.amount }
        )

        val bruto = redondear(litros * tarifa.lecheBase)
        val totalDeducciones = redondear(penalidadAgua + descuentoQueso)
        val neto = max(0.0, redondear(bruto - totalDeducciones))

        val autorizada = liquidaciones.firstOrNull { it.status == "autorizado" }
        val pagadaHoy = liquidaciones.any { it.status == "pagado" && it.pagadoEn?.take(10) == hoy }

        return Sobre(
            productorId = productorId,
            desde = desde,
            hasta = hoy,
            litros = litros,
            precioBase = tarifa.lecheBase,
            precioEfectivo = precio.precio,
            bruto = autorizada?.bruto ?: bruto,
            descuentoQueso = descuentoQueso,
            penalidadAgua = penalidadAgua,
            penalidadPorLitro = penalidadPorLitro,
            totalDeducciones = autorizada?.deducciones ?: totalDeducciones,
            neto = autorizada?.neto ?: neto,
            hayAdulteracion = precio.penalidad != Penalidad.NINGUNA,
            porcentajeAgua = precio.porcentajeAgua,
            autorizado = autorizada != null,
            pagado = pagadaHoy,
        )
    }

    // ------------------------------------------------------------ RECEPCIÓN

    /** Diferencia entre lo declarado en ruta y lo medido por el caudalímetro. */
    fun merma(litrosDeclarados: Double, litrosCaudalimetro: Double): Double =
        redondear(litrosCaudalimetro - litrosDeclarados)

    fun estadoSugerido(litrosDeclarados: Double, litrosCaudalimetro: Double): String {
        val diferencia = litrosCaudalimetro - litrosDeclarados
        return when {
            abs(diferencia) < 0.01 -> "verificado"
            diferencia < 0 -> "incompleto"
            else -> "con_observacion"
        }
    }

    fun redondear(valor: Double): Double = round(valor * 100) / 100
}
