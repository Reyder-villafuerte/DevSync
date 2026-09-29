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
import androidx.compose.material3.HorizontalDivider
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
import com.example.milkflowmovil.core.soles
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.datos.local.sobreDe
import com.example.milkflowmovil.dominio.Pantalla
import com.example.milkflowmovil.ui.Navegador
import com.example.milkflowmovil.ui.componentes.BotonPrimario
import com.example.milkflowmovil.ui.componentes.Campo
import com.example.milkflowmovil.ui.componentes.EncabezadoSeccion
import com.example.milkflowmovil.ui.componentes.Etiqueta
import com.example.milkflowmovil.ui.componentes.FilaDato
import com.example.milkflowmovil.ui.componentes.MensajeVacio
import com.example.milkflowmovil.ui.componentes.Nota
import com.example.milkflowmovil.ui.componentes.Tarjeta
import com.example.milkflowmovil.ui.componentes.TarjetaMetrica
import com.example.milkflowmovil.ui.tema.coloresMilkFlow

/**
 * Portal del proveedor: cuánta leche entregó, a cuánto se la pagan y qué le
 * están descontando. Todo sale de lo ya sincronizado, así que se puede
 * consultar en el establo sin señal.
 */
@Composable
fun PantallaMiAcopio(estado: EstadoLocal) {
    val yo = estado.sesion?.usuario ?: return
    var agrupacion by remember { mutableStateOf("dia") }

    val sobre = estado.sobreDe(yo.id)

    val misEntregas = estado.entregas
        .filter { it.productorId == yo.id }
        .mapNotNull { entrega -> estado.ruta(entrega.rutaId)?.let { entrega to it } }
        .sortedByDescending { it.second.date }

    val entregaHoy = misEntregas.firstOrNull { it.second.date == Fechas.hoy() }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Entregado hoy",
                    (entregaHoy?.first?.liters ?: 0.0).litros(),
                    entregaHoy?.let { Fechas.horaCorta(it.first.hora) } ?: "sin registro aún",
                    coloresMilkFlow.acento,
                    Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Litros del ciclo",
                    sobre.litros.litros(),
                    "desde ${Fechas.corta(sobre.desde)}",
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            Tarjeta {
                Text("Mi pago acumulado", style = MaterialTheme.typography.titleMedium)
                Spacer(Modifier.height(4.dp))
                Text(
                    "Se reinicia cuando administración paga el ciclo.",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )

                Spacer(Modifier.height(12.dp))
                FilaDato("Precio por litro", sobre.precioEfectivo.soles())
                FilaDato("Bruto de la semana", sobre.bruto.soles())

                if (sobre.descuentoQueso > 0) {
                    FilaDato("Compras de queso", "- ${sobre.descuentoQueso.soles()}", color = coloresMilkFlow.peligro)
                }
                if (sobre.penalidadAgua > 0) {
                    FilaDato("Penalidad por agua", "- ${sobre.penalidadAgua.soles()}", color = coloresMilkFlow.peligro)
                }

                Spacer(Modifier.height(6.dp))
                HorizontalDivider(color = coloresMilkFlow.borde)
                Spacer(Modifier.height(6.dp))
                FilaDato("Neto a cobrar", sobre.neto.soles(), resaltado = true, color = coloresMilkFlow.acento)

                if (sobre.autorizado) {
                    Spacer(Modifier.height(10.dp))
                    Nota("Tu pago ya fue autorizado. El pagador te lo entrega en la ruta del viernes.", coloresMilkFlow.exito, "✅")
                }

                if (sobre.hayAdulteracion) {
                    Spacer(Modifier.height(10.dp))
                    Nota(
                        "Se detectó ${sobre.porcentajeAgua}% de agua en tu leche. El descuento de " +
                            "${sobre.penalidadPorLitro.soles()} por litro se aplica a toda la semana.",
                        coloresMilkFlow.peligro, "💧",
                    )
                }
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                listOf("dia" to "Por día", "semana" to "Por semana", "mes" to "Por mes").forEach { (clave, texto) ->
                    FilterChip(agrupacion == clave, { agrupacion = clave }, { Text(texto) })
                }
            }
        }

        item { EncabezadoSeccion("Mis entregas") }

        if (misEntregas.isEmpty()) {
            item { MensajeVacio("Todavía no tienes entregas registradas.", "🥛") }
        }

        when (agrupacion) {
            "semana" -> {
                val porSemana = misEntregas.groupBy { Fechas.inicioSemana(it.second.date) }
                items(porSemana.entries.sortedByDescending { it.key }.toList(), key = { it.key }) { (inicio, lista) ->
                    val litros = lista.sumOf { it.first.liters }
                    Tarjeta {
                        Text("Semana del ${Fechas.corta(inicio)}", fontWeight = FontWeight.Bold)
                        Spacer(Modifier.height(6.dp))
                        FilaDato("Días entregados", "${lista.size}")
                        FilaDato("Litros", litros.litros(), resaltado = true)
                        FilaDato("Bruto estimado", (litros * sobre.precioBase).soles())
                    }
                }
            }

            "mes" -> {
                val porMes = misEntregas.groupBy { Fechas.mesDe(it.second.date) }
                items(porMes.entries.sortedByDescending { it.key }.toList(), key = { it.key }) { (mes, lista) ->
                    val litros = lista.sumOf { it.first.liters }
                    Tarjeta {
                        Text(Fechas.nombreMes(mes), fontWeight = FontWeight.Bold)
                        Spacer(Modifier.height(6.dp))
                        FilaDato("Días entregados", "${lista.size}")
                        FilaDato("Litros", litros.litros(), resaltado = true)
                        FilaDato("Bruto estimado", (litros * sobre.precioBase).soles())
                    }
                }
            }

            else -> {
                items(misEntregas, key = { it.first.id }) { (entrega, ruta) ->
                    Tarjeta {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                Text(
                                    "${Fechas.nombreDia(ruta.date)} ${Fechas.corta(ruta.date)}",
                                    fontWeight = FontWeight.Bold,
                                )
                                Text(
                                    "${Fechas.horaCorta(entrega.hora)} · ${estado.usuario(ruta.acopiadorId)?.name ?: "Acopiador"}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = coloresMilkFlow.textoSuave,
                                )
                            }
                            Text(entrega.liters.litros(), fontWeight = FontWeight.Black)
                        }
                        if (!entrega.notes.isNullOrBlank()) {
                            Spacer(Modifier.height(8.dp))
                            Nota(entrega.notes, coloresMilkFlow.info, "📝")
                        }
                    }
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

/** Solicitud de cambio de zona del productor. */
@Composable
fun PantallaMiZona(repositorio: Repositorio, estado: EstadoLocal) {
    val yo = estado.sesion?.usuario ?: return
    var zonaElegida by remember { mutableStateOf<Long?>(null) }
    var motivo by remember { mutableStateOf("") }
    var enviado by remember { mutableStateOf(false) }

    val misSolicitudes = estado.solicitudesZona.filter { it.productorId == yo.id }.sortedByDescending { it.id }
    val tienePendiente = misSolicitudes.any { it.status == "pendiente" }
    val destinos = estado.zonas.filter { it.activa && it.id != yo.zonaId }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            TarjetaMetrica(
                "Mi zona actual",
                estado.zona(yo.zonaId)?.name ?: "Sin zona asignada",
                estado.zona(yo.zonaId)?.description ?: "",
                coloresMilkFlow.acento,
                Modifier.fillMaxWidth(),
            )
        }

        if (tienePendiente) {
            item {
                Nota("Ya tienes una solicitud esperando revisión de administración.", coloresMilkFlow.aviso, "⏳")
            }
        } else {
            item {
                Tarjeta {
                    Text("Solicitar cambio de zona", style = MaterialTheme.typography.titleMedium)
                    Spacer(Modifier.height(10.dp))

                    destinos.forEach { zona ->
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            FilterChip(
                                zonaElegida == zona.id,
                                { zonaElegida = if (zonaElegida == zona.id) null else zona.id },
                                { Text(zona.name) },
                            )
                        }
                        Spacer(Modifier.height(6.dp))
                    }

                    if (destinos.isEmpty()) {
                        Text(
                            "No hay otras zonas disponibles por ahora.",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }

                    Spacer(Modifier.height(8.dp))
                    Campo(motivo, "Motivo de la solicitud", { motivo = it }, lineas = 3)

                    if (enviado) {
                        Spacer(Modifier.height(10.dp))
                        Nota("Solicitud enviada a administración.", coloresMilkFlow.exito, "✅")
                    }

                    Spacer(Modifier.height(12.dp))
                    BotonPrimario(
                        "Enviar solicitud",
                        Modifier.fillMaxWidth(),
                        habilitado = zonaElegida != null && motivo.isNotBlank(),
                    ) {
                        repositorio.solicitarCambioZona(zonaElegida!!, motivo)
                        motivo = ""
                        zonaElegida = null
                        enviado = true
                    }
                }
            }
        }

        item { EncabezadoSeccion("Mis solicitudes") }

        if (misSolicitudes.isEmpty()) {
            item { MensajeVacio("No has solicitado cambios de zona.", "🗺️") }
        }

        items(misSolicitudes, key = { it.id }) { solicitud ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(estado.zona(solicitud.zonaSolicitadaId)?.name ?: "Zona", fontWeight = FontWeight.Bold)
                        Text(
                            "Desde ${estado.zona(solicitud.zonaActualId)?.name ?: "—"}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Etiqueta(
                        solicitud.status.replaceFirstChar { it.uppercase() },
                        when (solicitud.status) {
                            "aprobado" -> coloresMilkFlow.exito
                            "rechazado" -> coloresMilkFlow.peligro
                            else -> coloresMilkFlow.aviso
                        },
                    )
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

/** Descuentos del proveedor: compras de queso y penalidades por calidad. */
@Composable
fun PantallaMisDescuentos(estado: EstadoLocal) {
    val yo = estado.sesion?.usuario ?: return

    val mios = estado.descuentos.filter { it.productorId == yo.id }.sortedByDescending { it.date }
    val pendiente = mios.filter { it.status == "pendiente" }.sumOf { it.amount }
    val descontado = mios.filter { it.status == "descontado" }.sumOf { it.amount }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Por descontar", pendiente.soles(), "en tu próximo pago", coloresMilkFlow.aviso, Modifier.weight(1f))
                TarjetaMetrica("Ya descontado", descontado.soles(), "histórico", modifier = Modifier.weight(1f))
            }
        }

        item {
            Nota(
                "Solo se descuentan compras de queso y penalidades por adulteración. No hay adelantos en efectivo.",
                coloresMilkFlow.info, "ℹ️",
            )
        }

        item { EncabezadoSeccion("Detalle de descuentos") }

        if (mios.isEmpty()) {
            item { MensajeVacio("No tienes descuentos registrados.", "➖") }
        }

        items(mios, key = { it.id }) { descuento ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(descuento.concept, style = MaterialTheme.typography.bodyMedium)
                        Text(
                            Fechas.corta(descuento.date),
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text("- ${descuento.amount.soles()}", fontWeight = FontWeight.Black, color = coloresMilkFlow.peligro)
                        Etiqueta(
                            if (descuento.status == "pendiente") "Pendiente" else "Descontado",
                            if (descuento.status == "pendiente") coloresMilkFlow.aviso else coloresMilkFlow.textoSuave,
                        )
                    }
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

/** Historial de liquidaciones cobradas por el proveedor. */
@Composable
fun PantallaMisPagos(estado: EstadoLocal, navegador: Navegador) {
    val yo = estado.sesion?.usuario ?: return

    val mias = estado.liquidaciones.filter { it.productorId == yo.id }.sortedByDescending { it.hasta }
    val cobrado = mias.filter { it.status == "pagado" }.sumOf { it.neto }
    val porCobrar = mias.filter { it.status != "pagado" }.sumOf { it.neto }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Cobrado", cobrado.soles(), "histórico", coloresMilkFlow.exito, Modifier.weight(1f))
                TarjetaMetrica("Por cobrar", porCobrar.soles(), "autorizado", coloresMilkFlow.acento, Modifier.weight(1f))
            }
        }

        item { EncabezadoSeccion("Mis liquidaciones") }

        if (mias.isEmpty()) {
            item { MensajeVacio("Todavía no tienes liquidaciones.", "💰") }
        }

        items(mias, key = { it.id }) { liquidacion ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(liquidacion.codigo, fontWeight = FontWeight.Bold)
                        Text(
                            "${Fechas.corta(liquidacion.desde)} — ${Fechas.corta(liquidacion.hasta)}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text(liquidacion.neto.soles(), fontWeight = FontWeight.Black)
                        Etiqueta(
                            liquidacion.status.replaceFirstChar { it.uppercase() },
                            when (liquidacion.status) {
                                "pagado" -> coloresMilkFlow.exito
                                "autorizado" -> coloresMilkFlow.acento
                                else -> coloresMilkFlow.aviso
                            },
                        )
                    }
                }

                Spacer(Modifier.height(10.dp))
                FilaDato("Litros", liquidacion.litros.litros())
                FilaDato("Precio por litro", liquidacion.precioLitro.soles())
                FilaDato("Bruto", liquidacion.bruto.soles())
                FilaDato("Deducciones", "- ${liquidacion.deducciones.soles()}", color = coloresMilkFlow.peligro)
                FilaDato("Neto", liquidacion.neto.soles(), resaltado = true)

                if (liquidacion.status == "pagado") {
                    FilaDato("Pagado el", Fechas.corta(liquidacion.pagadoEn))
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

/** Resultados Lactoscan del proveedor y sus visitas técnicas. */
@Composable
fun PantallaMiCalidad(estado: EstadoLocal) {
    val yo = estado.sesion?.usuario ?: return

    val mios = estado.analisis.filter { it.productorId == yo.id }.sortedByDescending { it.fecha }
    val visitas = estado.visitas.filter { it.productorId == yo.id && it.status == "programada" }

    val promedioGrasa = mios.mapNotNull { it.grasa }.takeIf { it.isNotEmpty() }?.average()
    val promedioAcidez = mios.mapNotNull { it.acidez }.takeIf { it.isNotEmpty() }?.average()

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Grasa promedio",
                    promedioGrasa?.let { "${(kotlin.math.round(it * 10) / 10)} %" } ?: "—",
                    "de tus análisis",
                    modifier = Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Acidez promedio",
                    promedioAcidez?.let { "${(kotlin.math.round(it * 10) / 10)}" } ?: "—",
                    "Dornic o pH",
                    modifier = Modifier.weight(1f),
                )
            }
        }

        if (visitas.isNotEmpty()) {
            item { EncabezadoSeccion("Visitas técnicas programadas") }

            items(visitas, key = { "v-${it.id}" }) { visita ->
                Tarjeta {
                    Text(
                        "${Fechas.nombreDia(visita.fecha)} ${Fechas.corta(visita.fecha)} · ${Fechas.horaCorta(visita.hora)}",
                        fontWeight = FontWeight.Bold,
                    )
                    Spacer(Modifier.height(6.dp))
                    Text(visita.reason, style = MaterialTheme.typography.bodySmall)
                }
            }
        }

        item { EncabezadoSeccion("Resultados de mi leche") }

        if (mios.isEmpty()) {
            item { MensajeVacio("Todavía no hay análisis de tu leche.", "🔬") }
        }

        items(mios, key = { it.id }) { analisis ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(Fechas.corta(analisis.fecha), fontWeight = FontWeight.Bold)
                        Text(
                            "Grasa ${analisis.grasa ?: "—"}% · Densidad ${analisis.density ?: "—"}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Etiqueta(analisis.veredicto.etiqueta, colorVeredicto(analisis.veredicto))
                }

                if ((analisis.agua ?: 0.0) > 0) {
                    Spacer(Modifier.height(10.dp))
                    Nota(
                        "Se detectó ${analisis.agua}% de agua. Esto reduce el precio de tu leche en toda la semana.",
                        coloresMilkFlow.peligro, "💧",
                    )
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}
