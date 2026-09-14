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
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.contieneTexto
import com.example.milkflowmovil.core.litros
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.dominio.Analisis
import com.example.milkflowmovil.dominio.Veredicto
import com.example.milkflowmovil.dominio.VisitaTecnica
import com.example.milkflowmovil.ui.componentes.BotonPrimario
import com.example.milkflowmovil.ui.componentes.BotonSecundario
import com.example.milkflowmovil.ui.componentes.Campo
import com.example.milkflowmovil.ui.componentes.EncabezadoSeccion
import com.example.milkflowmovil.ui.componentes.Etiqueta
import com.example.milkflowmovil.ui.componentes.FilaDato
import com.example.milkflowmovil.ui.componentes.MensajeVacio
import com.example.milkflowmovil.ui.componentes.Nota
import com.example.milkflowmovil.ui.componentes.Tarjeta
import com.example.milkflowmovil.ui.componentes.TarjetaMetrica
import com.example.milkflowmovil.ui.tema.coloresMilkFlow

@Composable
fun colorVeredicto(veredicto: Veredicto): Color = when (veredicto) {
    Veredicto.CONFORME -> coloresMilkFlow.exito
    Veredicto.ACIDEZ_ALTA -> coloresMilkFlow.aviso
    Veredicto.ADULTERADA -> coloresMilkFlow.peligro
    Veredicto.SOSPECHOSA -> coloresMilkFlow.info
}

/**
 * Control Lactoscan y agenda de visitas técnicas.
 *
 * Un veredicto de acidez alta agenda sola la visita al productor; el agua
 * detectada aquí es lo que después penaliza su liquidación de la semana.
 */
@Composable
fun PantallaCalidad(repositorio: Repositorio, estado: EstadoLocal) {
    val soloLectura = estado.sesion?.usuario?.role == "admin"

    var busqueda by remember { mutableStateOf("") }
    var filtro by remember { mutableStateOf<Veredicto?>(null) }
    var mostrarFormulario by remember { mutableStateOf(false) }
    var visitaEnCierre by remember { mutableStateOf<VisitaTecnica?>(null) }

    val analisis = estado.analisis
        .filter { filtro == null || it.veredicto == filtro }
        .filter { (estado.usuario(it.productorId)?.name ?: "").contieneTexto(busqueda) }
        .sortedByDescending { it.fecha }

    val visitasHoy = estado.visitas.filter { it.fecha == Fechas.hoy() && it.status == "programada" }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Análisis", "${estado.analisis.size}", "registrados", modifier = Modifier.weight(1f))
                TarjetaMetrica(
                    "Con agua",
                    "${estado.analisis.count { (it.agua ?: 0.0) > 0 }}",
                    "leche adulterada",
                    coloresMilkFlow.peligro,
                    Modifier.weight(1f),
                )
            }
        }

        if (visitasHoy.isNotEmpty()) {
            item { EncabezadoSeccion("Citas técnicas de hoy", "Visitas programadas al productor.") }

            items(visitasHoy, key = { "visita-${it.id}" }) { visita ->
                Tarjeta {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text(
                                estado.usuario(visita.productorId)?.name ?: "Productor",
                                fontWeight = FontWeight.Bold,
                            )
                            Text(
                                "${Fechas.horaCorta(visita.hora)} · ${visita.reason}",
                                style = MaterialTheme.typography.bodySmall,
                                color = coloresMilkFlow.textoSuave,
                            )
                        }
                        if (!soloLectura) {
                            BotonSecundario("Cerrar visita") { visitaEnCierre = visita }
                        }
                    }
                }
            }
        }

        if (!soloLectura) {
            item {
                BotonPrimario(
                    if (mostrarFormulario) "Ocultar formulario" else "+ Registrar análisis Lactoscan",
                    Modifier.fillMaxWidth(),
                ) { mostrarFormulario = !mostrarFormulario }
            }

            if (mostrarFormulario) {
                item {
                    FormularioAnalisis(repositorio, estado) { mostrarFormulario = false }
                }
            }
        } else {
            item {
                Nota(
                    "Administración solo consulta el historial de calidad; el registro lo hace el inspector.",
                    coloresMilkFlow.info, "ℹ️",
                )
            }
        }

        item {
            Column {
                EncabezadoSeccion("Historial de análisis")
                Campo(busqueda, "Buscar productor", { busqueda = it })
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    FilterChip(filtro == null, { filtro = null }, { Text("Todos") })
                    Veredicto.entries.forEach { veredicto ->
                        FilterChip(filtro == veredicto, { filtro = veredicto }, { Text(veredicto.etiqueta) })
                    }
                }
            }
        }

        if (analisis.isEmpty()) {
            item { MensajeVacio("No hay análisis que coincidan.", "🔬") }
        }

        items(analisis, key = { it.id }) { registro ->
            TarjetaAnalisis(registro, estado.usuario(registro.productorId)?.name ?: "Productor")
        }

        item { Spacer(Modifier.height(24.dp)) }
    }

    visitaEnCierre?.let { visita ->
        var informe by remember(visita.id) { mutableStateOf("") }

        AlertDialog(
            onDismissRequest = { visitaEnCierre = null },
            title = { Text("Cerrar visita técnica") },
            text = {
                Column {
                    Text(
                        estado.usuario(visita.productorId)?.name ?: "Productor",
                        fontWeight = FontWeight.Bold,
                    )
                    Spacer(Modifier.height(8.dp))
                    Campo(informe, "Informe de la visita", { informe = it }, lineas = 3)
                }
            },
            confirmButton = {
                TextButton(
                    enabled = informe.isNotBlank(),
                    onClick = {
                        repositorio.completarVisita(visita, informe)
                        visitaEnCierre = null
                    },
                ) { Text("Marcar realizada") }
            },
            dismissButton = { TextButton(onClick = { visitaEnCierre = null }) { Text("Cancelar") } },
        )
    }
}

@Composable
private fun FormularioAnalisis(repositorio: Repositorio, estado: EstadoLocal, alGuardar: () -> Unit) {
    var busqueda by remember { mutableStateOf("") }
    var productorId by remember { mutableStateOf<Long?>(null) }
    var grasa by remember { mutableStateOf("") }
    var solidos by remember { mutableStateOf("") }
    var densidad by remember { mutableStateOf("") }
    var proteina by remember { mutableStateOf("") }
    var agua by remember { mutableStateOf("") }
    var temperatura by remember { mutableStateOf("") }
    var acidez by remember { mutableStateOf("") }
    var veredicto by remember { mutableStateOf(Veredicto.CONFORME) }
    var notas by remember { mutableStateOf("") }
    var agendar by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    val candidatos = estado.productores.filter { it.name.contieneTexto(busqueda) }.take(6)
    val elegido = estado.usuario(productorId)

    // La leche entregada hoy por ese productor, para no analizar a ciegas.
    val entregaHoy = estado.entregas.firstOrNull { entrega ->
        entrega.productorId == productorId && estado.ruta(entrega.rutaId)?.date == Fechas.hoy()
    }

    Tarjeta {
        Text("Nuevo análisis", style = MaterialTheme.typography.titleMedium)
        Spacer(Modifier.height(10.dp))

        if (elegido == null) {
            Campo(busqueda, "Buscar productor", { busqueda = it })
            Spacer(Modifier.height(6.dp))
            candidatos.forEach { productor ->
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(productor.name, style = MaterialTheme.typography.bodyMedium)
                        Text(
                            estado.zona(productor.zonaId)?.name ?: "Sin zona",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    BotonSecundario("Elegir") { productorId = productor.id }
                }
            }
        } else {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(elegido.name, fontWeight = FontWeight.Bold)
                    Text(
                        entregaHoy?.let { "Entregó ${it.liters.litros()} hoy" } ?: "Sin entrega registrada hoy",
                        style = MaterialTheme.typography.bodySmall,
                        color = coloresMilkFlow.textoSuave,
                    )
                }
                BotonSecundario("Cambiar") { productorId = null }
            }

            Spacer(Modifier.height(12.dp))

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Campo(grasa, "Grasa %", { grasa = it }, Modifier.weight(1f), decimal = true)
                Campo(solidos, "Sólidos %", { solidos = it }, Modifier.weight(1f), decimal = true)
            }
            Spacer(Modifier.height(8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Campo(densidad, "Densidad", { densidad = it }, Modifier.weight(1f), decimal = true)
                Campo(proteina, "Proteína %", { proteina = it }, Modifier.weight(1f), decimal = true)
            }
            Spacer(Modifier.height(8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Campo(agua, "Agua añadida %", { agua = it }, Modifier.weight(1f), decimal = true)
                Campo(temperatura, "Temp. °C", { temperatura = it }, Modifier.weight(1f), decimal = true)
            }
            Spacer(Modifier.height(8.dp))
            Campo(acidez, "Acidez Dornic o pH", { acidez = it }, decimal = true)

            Spacer(Modifier.height(10.dp))
            Text("Veredicto", style = MaterialTheme.typography.labelLarge)
            Spacer(Modifier.height(6.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                Veredicto.entries.forEach { opcion ->
                    FilterChip(veredicto == opcion, { veredicto = opcion }, { Text(opcion.etiqueta) })
                }
            }

            val porcentajeAgua = agua.toDoubleOrNull() ?: 0.0
            if (porcentajeAgua > 0) {
                Spacer(Modifier.height(10.dp))
                Nota(
                    if (porcentajeAgua > 5) {
                        "Agua sobre el 5%: la leche baja a ${estado.tarifaVigente.lecheAguaGrave} S//L y el productor queda en riesgo de expulsión."
                    } else {
                        "Agua detectada: la leche baja a ${estado.tarifaVigente.lecheAguaLeve} S//L toda la semana."
                    },
                    coloresMilkFlow.peligro, "💧",
                )
            }

            Spacer(Modifier.height(10.dp))
            Campo(notas, "Observaciones", { notas = it }, lineas = 2)

            Spacer(Modifier.height(10.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                FilterChip(agendar, { agendar = !agendar }, { Text("Agendar visita técnica") })
            }

            if (error != null) {
                Spacer(Modifier.height(10.dp))
                Nota(error!!, coloresMilkFlow.peligro, "⚠️")
            }

            Spacer(Modifier.height(12.dp))
            BotonPrimario("Guardar análisis", Modifier.fillMaxWidth()) {
                repositorio.registrarAnalisis(
                    productorId = elegido.id,
                    grasa = grasa.toDoubleOrNull(),
                    solidos = solidos.toDoubleOrNull(),
                    densidad = densidad.toDoubleOrNull(),
                    proteina = proteina.toDoubleOrNull(),
                    agua = agua.toDoubleOrNull(),
                    temperatura = temperatura.toDoubleOrNull(),
                    acidez = acidez.toDoubleOrNull(),
                    veredicto = veredicto.clave,
                    notas = notas.ifBlank { null },
                    agendarVisita = agendar,
                    fechaVisita = if (agendar) Fechas.sumarDias(Fechas.hoy(), 2) else null,
                    motivoVisita = null,
                )
                alGuardar()
            }
        }
    }
}

@Composable
private fun TarjetaAnalisis(analisis: Analisis, productor: String) {
    var abierto by remember { mutableStateOf(false) }

    Tarjeta(alPulsar = { abierto = !abierto }) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(productor, fontWeight = FontWeight.Bold)
                Text(
                    Fechas.corta(analisis.fecha),
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )
            }
            Column(horizontalAlignment = Alignment.End) {
                Etiqueta(analisis.veredicto.etiqueta, colorVeredicto(analisis.veredicto))
                if (analisis.pendiente) {
                    Spacer(Modifier.height(4.dp))
                    Etiqueta("Sin subir", coloresMilkFlow.aviso)
                }
            }
        }

        if (abierto) {
            Spacer(Modifier.height(10.dp))
            FilaDato("Grasa", analisis.grasa?.let { "$it %" } ?: "—")
            FilaDato("Sólidos no grasos", analisis.solidos?.let { "$it %" } ?: "—")
            FilaDato("Densidad", analisis.density?.toString() ?: "—")
            FilaDato("Proteína", analisis.proteina?.let { "$it %" } ?: "—")
            FilaDato(
                "Agua añadida",
                analisis.agua?.let { "$it %" } ?: "—",
                color = if ((analisis.agua ?: 0.0) > 0) coloresMilkFlow.peligro else null,
            )
            FilaDato("Temperatura", analisis.temperature?.let { "$it °C" } ?: "—")
            FilaDato("Acidez / pH", analisis.acidez?.toString() ?: "—")

            if (!analisis.notes.isNullOrBlank()) {
                Spacer(Modifier.height(8.dp))
                Nota(analisis.notes, coloresMilkFlow.info, "📝")
            }
        }
    }
}
