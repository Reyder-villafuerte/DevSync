package com.example.milkflowmovil

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.litros
import com.example.milkflowmovil.core.sinAcentos
import com.example.milkflowmovil.core.soles
import com.example.milkflowmovil.dominio.Analisis
import com.example.milkflowmovil.dominio.Cliente
import com.example.milkflowmovil.dominio.Descuento
import com.example.milkflowmovil.dominio.Entrega
import com.example.milkflowmovil.dominio.Liquidacion
import com.example.milkflowmovil.dominio.Reglas
import com.example.milkflowmovil.dominio.Rol
import com.example.milkflowmovil.dominio.Ruta
import com.example.milkflowmovil.dominio.Pantalla
import com.example.milkflowmovil.dominio.Tarifa
import kotlinx.serialization.json.Json
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * Las reglas de Huata calculadas en el teléfono deben dar el mismo número que
 * el servidor: si divergen, el acopiador ve un precio y cobra otro.
 */
class ReglasTest {

    private val tarifa = Tarifa(
        id = 1,
        temporada = "Prueba",
        lecheBase = 1.40,
        lecheAguaLeve = 1.20,
        lecheAguaGrave = 0.90,
        quesoProveedor = 18.0,
        quesoMayorista = 19.0,
        quesoLocal = 20.0,
    )

    @Test
    fun el_precio_del_queso_sigue_el_tipo_de_cliente() {
        val proveedor = Cliente(id = 1, apellidos = "Quispe", type = "proveedor", usuarioVinculadoId = 7)
        val mayorista = Cliente(id = 2, apellidos = "Mamani", type = "mayorista")
        val local = Cliente(id = 3, apellidos = "Flores", type = "local")

        assertEquals(18.0, Reglas.precioQueso(proveedor, 1, tarifa))
        assertEquals(19.0, Reglas.precioQueso(mayorista, 1, tarifa))
        assertEquals(20.0, Reglas.precioQueso(local, 1, tarifa))

        // Diez moldes o más se cobran como mayorista aunque el cliente sea local.
        assertEquals(19.0, Reglas.precioQueso(local, 10, tarifa))
        assertEquals(20.0, Reglas.precioQueso(null, 2, tarifa))
    }

    @Test
    fun el_agua_detectada_baja_el_precio_por_litro() {
        val sinAgua = Reglas.precioLeche(emptyList(), tarifa)
        assertEquals(1.40, sinAgua.precio)
        assertEquals(Reglas.Penalidad.NINGUNA, sinAgua.penalidad)

        val leve = Reglas.precioLeche(listOf(analisis(agua = 3.0)), tarifa)
        assertEquals(1.20, leve.precio)
        assertEquals(Reglas.Penalidad.LEVE, leve.penalidad)

        val grave = Reglas.precioLeche(listOf(analisis(agua = 7.5)), tarifa)
        assertEquals(0.90, grave.precio)
        assertEquals(Reglas.Penalidad.GRAVE, grave.penalidad)
    }

    @Test
    fun manda_el_peor_analisis_del_periodo() {
        val resultado = Reglas.precioLeche(
            listOf(analisis(agua = 0.0), analisis(agua = 6.0), analisis(agua = 1.0)),
            tarifa,
        )

        assertEquals(0.90, resultado.precio, "Un solo día con 6% de agua arrastra toda la semana")
    }

    @Test
    fun la_penalidad_por_agua_se_aplica_a_todos_los_litros_de_la_semana() {
        val hoy = Fechas.hoy()
        val ayer = Fechas.hace(1)

        val rutas = mapOf(
            1L to Ruta(id = 1, date = ayer),
            2L to Ruta(id = 2, date = hoy),
        )

        val entregas = listOf(
            Entrega(id = 1, rutaId = 1, productorId = 9, liters = 60.0),
            Entrega(id = 2, rutaId = 2, productorId = 9, liters = 40.0),
        )

        val sobre = Reglas.calcularSobre(
            productorId = 9,
            entregas = entregas,
            rutas = rutas,
            // El agua se detectó solo un día: la penalidad alcanza a los 100 L.
            analisis = listOf(analisis(agua = 3.0, fecha = ayer)),
            descuentos = emptyList(),
            liquidaciones = emptyList(),
            tarifa = tarifa,
            hoy = hoy,
        )

        assertEquals(100.0, sobre.litros)
        assertEquals(140.0, sobre.bruto, "100 L al precio base de S/ 1.40")
        assertEquals(0.20, sobre.penalidadPorLitro)
        assertEquals(20.0, sobre.penalidadAgua, "S/ 0.20 por cada uno de los 100 L")
        assertEquals(120.0, sobre.neto)
        assertTrue(sobre.hayAdulteracion)
    }

    @Test
    fun las_compras_de_queso_se_restan_del_sobre() {
        val hoy = Fechas.hoy()
        val rutas = mapOf(1L to Ruta(id = 1, date = hoy))

        val sobre = Reglas.calcularSobre(
            productorId = 9,
            entregas = listOf(Entrega(id = 1, rutaId = 1, productorId = 9, liters = 50.0)),
            rutas = rutas,
            analisis = emptyList(),
            descuentos = listOf(
                Descuento(id = 1, productorId = 9, concept = "Compra de 2 moldes de queso", amount = 36.0),
                Descuento(id = 2, productorId = 9, concept = "Otro concepto", amount = 10.0),
                Descuento(id = 3, productorId = 9, concept = "Compra de queso", amount = 18.0, status = "descontado"),
            ),
            liquidaciones = emptyList(),
            tarifa = tarifa,
            hoy = hoy,
        )

        assertEquals(70.0, sobre.bruto)
        assertEquals(36.0, sobre.descuentoQueso, "Solo cuentan las compras de queso pendientes")
        assertEquals(34.0, sobre.neto)
    }

    @Test
    fun el_ciclo_abierto_empieza_tras_la_ultima_liquidacion_pagada() {
        val hoy = Fechas.hoy()
        val pagada = Liquidacion(
            id = 1,
            productorId = 9,
            desde = Fechas.hace(10),
            hasta = Fechas.hace(3),
            status = "pagado",
        )

        assertEquals(Fechas.hace(2), Reglas.inicioCiclo(listOf(pagada), hoy))
        assertEquals(Fechas.hace(6), Reglas.inicioCiclo(emptyList(), hoy), "Sin pagos previos, los últimos 7 días")
    }

    @Test
    fun el_estado_de_recepcion_sale_de_la_diferencia_con_el_caudalimetro() {
        assertEquals("verificado", Reglas.estadoSugerido(150.0, 150.0))
        assertEquals("incompleto", Reglas.estadoSugerido(150.0, 143.0))
        assertEquals("con_observacion", Reglas.estadoSugerido(150.0, 158.0))
        assertEquals(-7.0, Reglas.merma(150.0, 143.0))
    }

    @Test
    fun cada_rol_solo_alcanza_sus_pantallas() {
        assertTrue(Rol.ACOPIADOR.puedeVer(Pantalla.ACOPIO))
        assertFalse(Rol.ACOPIADOR.puedeVer(Pantalla.AUTORIZAR_PAGOS))
        assertFalse(Rol.PRODUCTOR.puedeVer(Pantalla.CAUDALIMETRO))
        assertTrue(Rol.PRODUCTOR.puedeVer(Pantalla.MI_ACOPIO))
        assertTrue(Rol.JEFE_GENERAL.puedeVer(Pantalla.CAUDALIMETRO))
        assertEquals(Pantalla.ACOPIO, Rol.ACOPIADOR.inicio)
        assertEquals(Pantalla.SOBRES_RUTA, Rol.PAGADOR_CAMPO.inicio)
    }

    private fun analisis(agua: Double, fecha: String = Fechas.hoy()) =
        Analisis(id = 1, productorId = 9, fecha = fecha, agua = agua)
}

/** El servidor manda decimales como texto o como número según el motor de BD. */
class SerializacionTest {

    private val json = Json { ignoreUnknownKeys = true; isLenient = true }

    @Test
    fun los_decimales_llegan_como_texto_o_como_numero() {
        val comoTexto = json.decodeFromString<Entrega>(
            """{"id":1,"collection_route_id":2,"producer_id":3,"liters":"18.50"}"""
        )
        val comoNumero = json.decodeFromString<Entrega>(
            """{"id":1,"collection_route_id":2,"producer_id":3,"liters":18.5}"""
        )

        assertEquals(18.5, comoTexto.liters)
        assertEquals(18.5, comoNumero.liters)
    }

    @Test
    fun los_booleanos_de_mysql_llegan_como_uno_o_cero() {
        val fila = json.decodeFromString<Cliente>(
            """{"id":1,"first_name":"Ana","last_name":"Pari","is_wholesale_approved":1}"""
        )

        assertTrue(fila.mayoristaAprobado)
    }

    @Test
    fun las_columnas_desconocidas_no_rompen_la_bajada() {
        val fila = json.decodeFromString<Ruta>(
            """{"id":4,"date":"2026-09-14","zone_id":1,"collector_id":2,"columna_nueva":"algo"}"""
        )

        assertEquals(4L, fila.id)
    }
}

/** Formato y fechas: lo que el acopiador lee en pantalla a las 4:30 de la mañana. */
class FormatoTest {

    @Test
    fun los_importes_y_litros_se_muestran_como_en_el_recibo() {
        assertEquals("S/ 1,234.50", 1234.5.soles())
        assertEquals("S/ 0.90", 0.9.soles())
        assertEquals("148.5 L", 148.5.litros())
    }

    @Test
    fun las_busquedas_ignoran_acentos_y_mayusculas() {
        assertTrue("Ñuñure".sinAcentos() == "nunure")
        assertTrue("José Pérez".sinAcentos().contains("jose perez"))
    }

    @Test
    fun las_fechas_se_leen_en_formato_peruano() {
        assertEquals("14/09/2026", Fechas.corta("2026-09-14"))
        assertEquals("14 de setiembre de 2026", Fechas.larga("2026-09-14T04:30:00Z"))
        assertEquals("Lunes", Fechas.nombreDia("2026-09-14"))
        assertEquals("04:45", Fechas.horaCorta("04:45:00"))
        assertEquals("—", Fechas.corta(null))
    }

    @Test
    fun el_calculo_de_dias_respeta_el_calendario() {
        assertEquals("2026-09-21", Fechas.sumarDias("2026-09-14", 7))
        assertEquals(7, Fechas.diasEntre("2026-09-14", "2026-09-21"))
        assertEquals("2026-09-14", Fechas.inicioSemana("2026-09-17"))
    }
}
