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
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.litros
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.ui.componentes.BotonPrimario
import com.example.milkflowmovil.ui.componentes.BotonSecundario
import com.example.milkflowmovil.ui.componentes.EncabezadoSeccion
import com.example.milkflowmovil.ui.componentes.Etiqueta
import com.example.milkflowmovil.ui.componentes.FilaDato
import com.example.milkflowmovil.ui.componentes.MensajeVacio
import com.example.milkflowmovil.ui.componentes.Nota
import com.example.milkflowmovil.ui.componentes.Tarjeta
import com.example.milkflowmovil.ui.componentes.TarjetaMetrica
import com.example.milkflowmovil.ui.tema.coloresMilkFlow

/** Las cuatro zonas de Huata, su padrón y quién las recorre hoy. */
@Composable
fun PantallaZonas(estado: EstadoLocal) {
    val hoy = Fechas.hoy()

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Zonas activas", "${estado.zonas.count { it.activa }}", "en Huata", modifier = Modifier.weight(1f))
                TarjetaMetrica("Proveedores", "${estado.productores.size}", "en el padrón", modifier = Modifier.weight(1f))
            }
        }

        item { EncabezadoSeccion("Zonas y rutas de hoy") }

        items(estado.zonas.sortedBy { it.code }, key = { it.id }) { zona ->
            val ruta = estado.rutas.firstOrNull { it.date == hoy && it.zonaId == zona.id }
            val productores = estado.productores.count { it.zonaId == zona.id }

            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(zona.name, fontWeight = FontWeight.Bold)
                        Text(
                            zona.description ?: "",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Etiqueta(
                        if (ruta != null) "Con acopiador" else "Sin ruta hoy",
                        if (ruta != null) coloresMilkFlow.exito else coloresMilkFlow.textoSuave,
                    )
                }

                Spacer(Modifier.height(10.dp))
                FilaDato("Proveedores", "$productores")
                FilaDato("Acopiador de hoy", estado.usuario(ruta?.acopiadorId)?.name ?: "—")
                FilaDato("Litros acopiados", (ruta?.litrosTotales ?: 0.0).litros())
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

/** Solicitudes de cambio de zona: aprobar mueve al productor de padrón. */
@Composable
fun PantallaSolicitudesZona(repositorio: Repositorio, estado: EstadoLocal) {
    var filtro by remember { mutableStateOf("pendiente") }

    val solicitudes = estado.solicitudesZona
        .filter { filtro == "todos" || it.status == filtro }
        .sortedByDescending { it.id }

    val pendientes = estado.solicitudesZona.count { it.status == "pendiente" }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Pendientes",
                    "$pendientes",
                    "esperan revisión",
                    if (pendientes > 0) coloresMilkFlow.aviso else null,
                    Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Aprobadas",
                    "${estado.solicitudesZona.count { it.status == "aprobado" }}",
                    "cambios hechos",
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                listOf("pendiente" to "Pendientes", "aprobado" to "Aprobadas", "rechazado" to "Rechazadas", "todos" to "Todas")
                    .forEach { (clave, texto) ->
                        FilterChip(filtro == clave, { filtro = clave }, { Text(texto) })
                    }
            }
        }

        if (solicitudes.isEmpty()) {
            item { MensajeVacio("No hay solicitudes en este filtro.", "📬") }
        }

        items(solicitudes, key = { it.id }) { solicitud ->
            Tarjeta {
                Text(
                    estado.usuario(solicitud.productorId)?.name ?: "Productor",
                    fontWeight = FontWeight.Bold,
                )
                Spacer(Modifier.height(8.dp))

                FilaDato("Zona actual", estado.zona(solicitud.zonaActualId)?.name ?: "—")
                FilaDato("Zona solicitada", estado.zona(solicitud.zonaSolicitadaId)?.name ?: "—", resaltado = true)

                if (!solicitud.reason.isNullOrBlank()) {
                    Spacer(Modifier.height(8.dp))
                    Nota(solicitud.reason, coloresMilkFlow.info, "💬")
                }

                Spacer(Modifier.height(12.dp))

                if (solicitud.status == "pendiente") {
                    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                        BotonPrimario("Aprobar", Modifier.weight(1f)) {
                            repositorio.revisarSolicitudZona(solicitud, "aprobado")
                        }
                        BotonSecundario("Rechazar", Modifier.weight(1f)) {
                            repositorio.revisarSolicitudZona(solicitud, "rechazado")
                        }
                    }
                } else {
                    Etiqueta(
                        solicitud.status.replaceFirstChar { it.uppercase() },
                        if (solicitud.status == "aprobado") coloresMilkFlow.exito else coloresMilkFlow.peligro,
                    )
                }

                if (solicitud.pendiente) {
                    Spacer(Modifier.height(8.dp))
                    Etiqueta("Sin subir", coloresMilkFlow.aviso)
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}
