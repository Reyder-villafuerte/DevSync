package com.example.milkflowmovil.ui.pantallas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.litros
import com.example.milkflowmovil.core.soles
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.dominio.EstadoRuta
import com.example.milkflowmovil.dominio.Pantalla
import com.example.milkflowmovil.dominio.Rol
import com.example.milkflowmovil.ui.Navegador
import com.example.milkflowmovil.ui.componentes.BotonSecundario
import com.example.milkflowmovil.ui.componentes.EncabezadoSeccion
import com.example.milkflowmovil.ui.componentes.Etiqueta
import com.example.milkflowmovil.ui.componentes.FilaDato
import com.example.milkflowmovil.ui.componentes.MensajeVacio
import com.example.milkflowmovil.ui.componentes.Nota
import com.example.milkflowmovil.ui.componentes.Tarjeta
import com.example.milkflowmovil.ui.componentes.TarjetaMetrica
import com.example.milkflowmovil.ui.tema.coloresMilkFlow

/**
 * Panel del día para los roles de oficina y supervisión.
 *
 * Resume la jornada: cuánta leche entró, en qué estado están las rutas y qué
 * hay pendiente de atender.
 */
@Composable
fun PantallaPanel(repositorio: Repositorio, estado: EstadoLocal, navegador: Navegador) {
    val hoy = Fechas.hoy()
    val rol = Rol.desde(estado.sesion?.usuario?.role)

    val rutasHoy = estado.rutas.filter { it.date == hoy }
    val litrosHoy = rutasHoy.sumOf { it.litrosTotales }
    val ventasHoy = estado.ventas.filter { it.vendidoEn.take(10) == hoy }
    val solicitudesPendientes = estado.solicitudesZona.count { it.status == "pendiente" }
    val avisosVigentes = estado.avisos.filter { it.desde <= hoy && it.hasta >= hoy && it.activo }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        if (avisosVigentes.isNotEmpty()) {
            items(avisosVigentes, key = { "aviso-${it.id}" }) { aviso ->
                Tarjeta {
                    Text("📢 ${aviso.title}", fontWeight = FontWeight.Bold)
                    Spacer(Modifier.height(6.dp))
                    Text(aviso.message, style = MaterialTheme.typography.bodySmall)
                }
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Acopiado hoy", litrosHoy.litros(), "${rutasHoy.size} rutas", coloresMilkFlow.acento, Modifier.weight(1f))
                TarjetaMetrica("Stock de leche", estado.stockLeche.litros(), "verificada", modifier = Modifier.weight(1f))
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Queso en stock", "${estado.stockQueso.toInt()}", "moldes", modifier = Modifier.weight(1f))
                TarjetaMetrica(
                    "Ventas de hoy",
                    ventasHoy.sumOf { it.total }.soles(),
                    "${ventasHoy.sumOf { it.moldes }} moldes",
                    coloresMilkFlow.exito,
                    Modifier.weight(1f),
                )
            }
        }

        if (solicitudesPendientes > 0 && rol.puedeVer(Pantalla.SOLICITUDES_ZONA)) {
            item {
                Tarjeta {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text("$solicitudesPendientes solicitudes de zona", fontWeight = FontWeight.Bold)
                            Text(
                                "Proveedores esperando cambio de padrón.",
                                style = MaterialTheme.typography.bodySmall,
                                color = coloresMilkFlow.textoSuave,
                            )
                        }
                        BotonSecundario("Revisar") { navegador.irDesdeMenu(Pantalla.SOLICITUDES_ZONA) }
                    }
                }
            }
        }

        item { EncabezadoSeccion("Rutas del día", "Estado de las cuatro zonas de Huata.") }

        if (rutasHoy.isEmpty()) {
            item { MensajeVacio("Todavía no se abren rutas hoy.", "🚚") }
        }

        items(rutasHoy.sortedBy { it.zonaId }, key = { it.id }) { ruta ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(estado.zona(ruta.zonaId)?.name ?: "Zona", fontWeight = FontWeight.Bold)
                        Text(
                            estado.usuario(ruta.acopiadorId)?.name ?: "Acopiador",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text(ruta.litrosTotales.litros(), fontWeight = FontWeight.Black)
                        Etiqueta(
                            ruta.estado.etiqueta,
                            when (ruta.estado) {
                                EstadoRuta.VERIFICADA -> coloresMilkFlow.exito
                                EstadoRuta.DESCARGADA -> coloresMilkFlow.aviso
                                else -> coloresMilkFlow.info
                            },
                        )
                    }
                }

                estado.recepcionDeRuta(ruta.id)?.let { recepcion ->
                    Spacer(Modifier.height(10.dp))
                    FilaDato("Caudalímetro", recepcion.litrosCaudalimetro.litros())
                    FilaDato(
                        "Diferencia",
                        recepcion.diferencia.litros(),
                        color = if (recepcion.diferencia < 0) coloresMilkFlow.peligro else coloresMilkFlow.exito,
                    )
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}
