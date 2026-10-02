package com.example.milkflowmovil.core

import kotlinx.datetime.DatePeriod
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.minus
import kotlinx.datetime.plus
import kotlinx.datetime.toLocalDateTime
import kotlin.time.Clock

/**
 * Fechas en formato ISO (yyyy-MM-dd), igual que las guarda el servidor.
 *
 * Todo el móvil trabaja con texto ISO para que una fila sincronizada y una
 * creada sin señal sean indistinguibles.
 */
object Fechas {

    private val DIAS = listOf("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo")

    private val MESES = listOf(
        "enero", "febrero", "marzo", "abril", "mayo", "junio",
        "julio", "agosto", "setiembre", "octubre", "noviembre", "diciembre"
    )

    /**
     * Fecha de la JORNADA, no del calendario (igual que JornadaOperativa en la web).
     *
     * La jornada empieza a las 4:30 AM, cuando sale el camión. Antes de esa hora
     * todavía se trabaja con la fecha del día anterior; así el teléfono y el
     * servidor siempre hablan de la misma ruta y del mismo cierre de caja.
     */
    fun hoy(): String {
        val ahora = ahoraLocal()
        val antesDelCorte = ahora.hour < HORA_CORTE || (ahora.hour == HORA_CORTE && ahora.minute < MINUTO_CORTE)
        return if (antesDelCorte) ahora.date.minus(DatePeriod(days = 1)).toString() else ahora.date.toString()
    }

    private const val HORA_CORTE = 4
    private const val MINUTO_CORTE = 30

    fun ahoraIso(): String = Clock.System.now().toString()

    fun horaActual(): String {
        val t = ahoraLocal().time
        return "${dosDigitos(t.hour)}:${dosDigitos(t.minute)}:${dosDigitos(t.second)}"
    }

    fun hace(dias: Int): String = LocalDate.parse(hoy()).minus(DatePeriod(days = dias)).toString()

    fun sumarDias(fechaIso: String, dias: Int): String =
        aFecha(fechaIso).plus(DatePeriod(days = dias)).toString()

    /** "14/09/2026" */
    fun corta(fechaIso: String?): String {
        val f = aFechaONula(fechaIso) ?: return "—"
        return "${dosDigitos(f.day)}/${dosDigitos(f.month.ordinal + 1)}/${f.year}"
    }

    /** "14 de setiembre de 2026" */
    fun larga(fechaIso: String?): String {
        val f = aFechaONula(fechaIso) ?: return "—"
        return "${f.day} de ${MESES[f.month.ordinal]} de ${f.year}"
    }

    /** "Jueves" */
    fun nombreDia(fechaIso: String?): String {
        val f = aFechaONula(fechaIso) ?: return ""
        return DIAS[f.dayOfWeek.ordinal]
    }

    /** Hora corta "04:45" a partir de "04:45:00" o de una marca ISO completa. */
    fun horaCorta(valor: String?): String {
        if (valor.isNullOrBlank()) return "—"
        val conHora = if (valor.contains('T')) valor.substringAfter('T') else valor
        return conHora.take(5)
    }

    fun diasEntre(desdeIso: String, hastaIso: String): Int {
        val a = aFecha(desdeIso).toEpochDays()
        val b = aFecha(hastaIso).toEpochDays()
        return (b - a).toInt()
    }

    fun esHoy(fechaIso: String?): Boolean = aFechaONula(fechaIso)?.toString() == hoy()

    /** Lunes de la semana de la fecha dada. */
    fun inicioSemana(fechaIso: String = hoy()): String {
        val f = aFecha(fechaIso)
        return f.minus(DatePeriod(days = f.dayOfWeek.ordinal)).toString()
    }

    fun clavesSemana(fechaIso: String): String {
        val inicio = inicioSemana(fechaIso)
        return "$inicio|${sumarDias(inicio, 6)}"
    }

    fun mesDe(fechaIso: String): String = fechaIso.take(7)

    fun nombreMes(clave: String): String {
        val partes = clave.split("-")
        val mes = partes.getOrNull(1)?.toIntOrNull() ?: return clave
        return "${MESES[mes - 1].replaceFirstChar { it.uppercase() }} ${partes[0]}"
    }

    private fun ahoraLocal() = Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault())

    /** Acepta "2026-09-14" o "2026-09-14T04:30:00Z" y se queda con la fecha. */
    private fun aFecha(valor: String): LocalDate = LocalDate.parse(valor.take(10))

    private fun aFechaONula(valor: String?): LocalDate? {
        if (valor.isNullOrBlank()) return null
        return runCatching { aFecha(valor) }.getOrNull()
    }

    private fun dosDigitos(n: Int): String = if (n < 10) "0$n" else "$n"
}

/** Soles con dos decimales: "S/ 1,234.50". */
fun Double.soles(): String = "S/ ${dosDecimales(this)}"

/** Litros con un decimal: "148.5 L". */
fun Double.litros(): String = "${unDecimal(this)} L"

fun dosDecimales(valor: Double): String {
    val redondeado = kotlin.math.round(valor * 100) / 100
    val entero = redondeado.toLong()
    val centavos = kotlin.math.round((kotlin.math.abs(redondeado) - kotlin.math.abs(entero.toDouble())) * 100).toInt()
    val signo = if (redondeado < 0 && entero == 0L) "-" else ""
    return "$signo${conMiles(entero)}.${if (centavos < 10) "0$centavos" else "$centavos"}"
}

fun unDecimal(valor: Double): String {
    val redondeado = kotlin.math.round(valor * 10) / 10
    val entero = redondeado.toLong()
    val decimas = kotlin.math.round((kotlin.math.abs(redondeado) - kotlin.math.abs(entero.toDouble())) * 10).toInt()
    return "${conMiles(entero)}.$decimas"
}

private fun conMiles(valor: Long): String {
    val texto = kotlin.math.abs(valor).toString()
    val grupos = StringBuilder()
    for ((indice, caracter) in texto.withIndex()) {
        if (indice > 0 && (texto.length - indice) % 3 == 0) grupos.append(',')
        grupos.append(caracter)
    }
    return if (valor < 0) "-$grupos" else grupos.toString()
}

/** Búsquedas tolerantes: "Nuñure" encuentra "Ñuñure". */
fun String.sinAcentos(): String {
    val origen = "áàäâãÁÀÄÂÃéèëêÉÈËÊíìïîÍÌÏÎóòöôõÓÒÖÔÕúùüûÚÙÜÛñÑçÇ"
    val destino = "aaaaaAAAAAeeeeEEEEiiiiIIIIoooooOOOOOuuuuUUUUnNcC"
    val salida = StringBuilder(length)
    for (caracter in this) {
        val indice = origen.indexOf(caracter)
        salida.append(if (indice >= 0) destino[indice] else caracter)
    }
    return salida.toString().lowercase()
}

fun String.contieneTexto(termino: String): Boolean =
    termino.isBlank() || sinAcentos().contains(termino.sinAcentos())
