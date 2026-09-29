package com.example.milkflowmovil.ui.pantallas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.AlertDialog
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.contieneTexto
import com.example.milkflowmovil.core.litros
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.dominio.EstadoRuta
import com.example.milkflowmovil.dominio.Entrega
import com.example.milkflowmovil.dominio.Ruta
import com.example.milkflowmovil.dominio.Usuario
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

/**
 * Planilla de acopio de las 4:30 AM.
 *
 * Es la pantalla crítica sin señal: registrar litros, ver quién falta y cerrar
 * la ruta tienen que funcionar con el teléfono completamente aislado.
 */
@Composable
fun PantallaAcopio(repositorio: Repositorio, estado: EstadoLocal) {
    val ruta = estado.rutas.firstOrNull {
        it.date == Fechas.hoy() && it.acopiadorId == estado.sesion?.usuario?.id
    }

    var busqueda by remember { mutableStateOf("") }
    var productorEnEdicion by remember { mutableStateOf<Usuario?>(null) }
    var confirmarCierre by remember { mutableStateOf(false) }

    if (ruta == null) {
        SinRutaAsignada(repositorio)
        return
    }

    val entregas = estado.entregasDeRuta(ruta.id).associateBy { it.productorId }
    val zona = estado.zona(ruta.zonaId)

    val productores = estado.productores
        .filter { it.zonaId == ruta.zonaId }
        .filter { it.name.contieneTexto(busqueda) || (it.dni ?: "").contains(busqueda) }
        // Los que faltan primero: la lista se va vaciando conforme avanza la ruta.
        .sortedBy { if (entregas.containsKey(it.id)) 1 else 0 }

    val acopiados = estado.entregasDeRuta(ruta.id).size
    val totalProductores = estado.productores.count { it.zonaId == ruta.zonaId }
    val cerrada = ruta.estado != EstadoRuta.ASIGNADA && ruta.estado != EstadoRuta.EN_RUTA

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Litros de la ruta",
                    ruta.litrosTotales.litros(),
                    zona?.name ?: "Zona",
                    coloresMilkFlow.acento,
                    Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Proveedores",
                    "$acopiados / $totalProductores",
                    "acopiados hoy",
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            Tarjeta {
                FilaDato("Estado de la ruta", ruta.estado.etiqueta, resaltado = true)
                FilaDato("Fecha", "${Fechas.nombreDia(ruta.date)} ${Fechas.corta(ruta.date)}")
                FilaDato("Hora de inicio", Fechas.horaCorta(ruta.horaInicio))

                if (ruta.pendiente) {
                    Spacer(Modifier.height(10.dp))
                    Nota(
                        "Esta ruta se abrió sin señal. Al sincronizar, la planta le asignará la zona definitiva.",
                        coloresMilkFlow.aviso, "📶",
                    )
                }

                if (!cerrada) {
                    Spacer(Modifier.height(12.dp))
                    BotonPrimario("Cerrar ruta y descargar en planta", Modifier.fillMaxWidth()) {
                        confirmarCierre = true
                    }
                } else if (ruta.estado == EstadoRuta.VERIFICADA) {
                    val recepcion = estado.recepcionDeRuta(ruta.id)
                    Spacer(Modifier.height(10.dp))
                    Nota(
                        "Planta midió ${(recepcion?.litrosCaudalimetro ?: 0.0).litros()} con el caudalímetro" +
                            (recepcion?.diferencia?.takeIf { it != 0.0 }
                                ?.let { " (${if (it < 0) "faltaron" else "sobraron"} ${kotlin.math.abs(it).litros()})" } ?: "") + ".",
                        if ((recepcion?.diferencia ?: 0.0) < 0) coloresMilkFlow.peligro else coloresMilkFlow.exito,
                        "✅",
                    )

                    if (!recepcion?.observation.isNullOrBlank()) {
                        Spacer(Modifier.height(8.dp))
                        Nota("Jefe de planta: ${recepcion?.observation}", coloresMilkFlow.info, "📝")
                    }
                } else {
                    Spacer(Modifier.height(10.dp))
                    Nota(
                        "Ruta descargada en planta. Ya no se pueden cambiar los litros; espera la verificación con caudalímetro.",
                        coloresMilkFlow.info, "🔒",
                    )
                }
            }
        }

        item {
            Campo(busqueda, "Buscar por nombre o DNI", { busqueda = it })
        }

        if (productores.isEmpty()) {
            item { MensajeVacio("No hay proveedores que coincidan con la búsqueda.", "🔎") }
        }

        items(productores, key = { it.id }) { productor ->
            FilaProductor(
                productor = productor,
                entrega = entregas[productor.id],
                zonaNombre = estado.zona(productor.zonaId)?.name,
                editable = !cerrada,
            ) { productorEnEdicion = productor }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }

    productorEnEdicion?.let { productor ->
        DialogoEntrega(
            productor = productor,
            entrega = entregas[productor.id],
            alCerrar = { productorEnEdicion = null },
            alGuardar = { litros, notas ->
                repositorio.registrarEntrega(productor.id, litros, notas)
                productorEnEdicion = null
            },
        )
    }

    if (confirmarCierre) {
        AlertDialog(
            onDismissRequest = { confirmarCierre = false },
            title = { Text("Cerrar la ruta") },
            text = {
                Text(
                    "Vas a descargar ${ruta.litrosTotales.litros()} en planta con $acopiados proveedores registrados. " +
                        "Después ya no podrás corregir los litros."
                )
            },
            confirmButton = {
                TextButton(onClick = {
                    repositorio.cerrarRuta()
                    confirmarCierre = false
                }) { Text("Sí, cerrar ruta") }
            },
            dismissButton = {
                TextButton(onClick = { confirmarCierre = false }) { Text("Seguir acopiando") }
            },
        )
    }
}

@Composable
private fun SinRutaAsignada(repositorio: Repositorio) {
    Column(
        Modifier.fillMaxSize().padding(20.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Tarjeta {
            Text("Sin ruta abierta para hoy", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(6.dp))
            Text(
                "Las cuatro zonas de Huata rotan entre los acopiadores. Abre tu ruta del día para empezar a " +
                    "registrar entregas; si no queda zona libre, hoy te toca descanso.",
                style = MaterialTheme.typography.bodySmall,
                color = coloresMilkFlow.textoSuave,
            )
            Spacer(Modifier.height(14.dp))
            BotonPrimario("Abrir mi ruta de hoy", Modifier.fillMaxWidth()) {
                repositorio.abrirRutaDeHoy()
            }
        }

        Nota(
            "Puedes abrir la ruta sin señal: la app la registra en el teléfono y la confirma al sincronizar.",
            coloresMilkFlow.info, "📶",
        )
    }
}

@Composable
private fun FilaProductor(
    productor: Usuario,
    entrega: Entrega?,
    zonaNombre: String?,
    editable: Boolean,
    alPulsar: () -> Unit,
) {
    Tarjeta(alPulsar = if (editable) alPulsar else null) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(productor.name, style = MaterialTheme.typography.bodyLarge, fontWeight = FontWeight.Bold)
                Text(
                    "DNI ${productor.dni ?: "—"} · ${zonaNombre ?: "Sin zona"}",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )
            }

            Column(horizontalAlignment = Alignment.End) {
                if (entrega != null) {
                    Text(
                        entrega.liters.litros(),
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Black,
                        color = coloresMilkFlow.exito,
                    )
                    Text(
                        Fechas.horaCorta(entrega.hora),
                        style = MaterialTheme.typography.labelSmall,
                        color = coloresMilkFlow.textoSuave,
                    )
                    if (entrega.pendiente) {
                        Spacer(Modifier.height(4.dp))
                        Etiqueta("Sin subir", coloresMilkFlow.aviso)
                    }
                } else {
                    Etiqueta(if (editable) "Registrar" else "Sin entrega", coloresMilkFlow.textoSuave)
                }
            }
        }
    }
}

@Composable
private fun DialogoEntrega(
    productor: Usuario,
    entrega: Entrega?,
    alCerrar: () -> Unit,
    alGuardar: (Double, String?) -> Unit,
) {
    var litros by remember { mutableStateOf(entrega?.liters?.toString()?.removeSuffix(".0") ?: "") }
    var notas by remember { mutableStateOf(entrega?.notes ?: "") }
    var error by remember { mutableStateOf<String?>(null) }

    AlertDialog(
        onDismissRequest = alCerrar,
        title = { Text(productor.name) },
        text = {
            Column {
                Text(
                    if (entrega == null) "Registra los litros recibidos." else "Corrige los litros registrados.",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )
                Spacer(Modifier.height(12.dp))
                Campo(litros, "Litros", { litros = it; error = null }, decimal = true)
                Spacer(Modifier.height(8.dp))
                Campo(notas, "Observación (opcional)", { notas = it })

                if (error != null) {
                    Spacer(Modifier.height(8.dp))
                    Nota(error!!, coloresMilkFlow.peligro, "⚠️")
                }
            }
        },
        confirmButton = {
            TextButton(onClick = {
                val valor = litros.toDoubleOrNull()
                if (valor == null || valor < 0.1) {
                    error = "Escribe los litros recibidos (mínimo 0.1)."
                } else {
                    alGuardar(valor, notas.ifBlank { null })
                }
            }) { Text("Guardar") }
        },
        dismissButton = { TextButton(onClick = alCerrar) { Text("Cancelar") } },
    )
}

/** Historial de rutas del acopiador con la merma que midió la planta. */
@Composable
fun PantallaHistorialRutas(repositorio: Repositorio, estado: EstadoLocal) {
    val esAcopiador = estado.sesion?.usuario?.role == "acopiador"
    val miId = estado.sesion?.usuario?.id

    val rutas = estado.rutas
        .filter { !esAcopiador || it.acopiadorId == miId }
        .sortedByDescending { it.date }

    val verificadas = rutas.mapNotNull { ruta -> estado.recepcionDeRuta(ruta.id)?.let { ruta to it } }
    val totalCampo = rutas.sumOf { it.litrosTotales }
    val totalPlanta = verificadas.sumOf { it.second.litrosCaudalimetro }
    val merma = totalPlanta - totalCampo

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Rutas", "${rutas.size}", "registradas", modifier = Modifier.weight(1f))
                TarjetaMetrica("Litros en campo", totalCampo.litros(), "declarados", modifier = Modifier.weight(1f))
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Caudalímetro", totalPlanta.litros(), "verificados en planta", modifier = Modifier.weight(1f))
                TarjetaMetrica(
                    "Diferencia",
                    merma.litros(),
                    if (merma < 0) "faltante acumulado" else "a favor",
                    if (merma < 0) coloresMilkFlow.peligro else coloresMilkFlow.exito,
                    Modifier.weight(1f),
                )
            }
        }

        item { EncabezadoSeccion("Trazabilidad por ruta", "Litros declarados frente a lo medido en planta.") }

        if (rutas.isEmpty()) {
            item { MensajeVacio("Todavía no hay rutas registradas.", "🚚") }
        }

        items(rutas, key = { it.id }) { ruta ->
            TarjetaRuta(ruta, estado)
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

@Composable
private fun TarjetaRuta(ruta: Ruta, estado: EstadoLocal) {
    val recepcion = estado.recepcionDeRuta(ruta.id)
    val entregas = estado.entregasDeRuta(ruta.id)
    var abierta by remember { mutableStateOf(false) }

    Tarjeta(alPulsar = { abierta = !abierta }) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(
                    "${Fechas.nombreDia(ruta.date)} ${Fechas.corta(ruta.date)}",
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    estado.zona(ruta.zonaId)?.name ?: "Zona",
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
                        EstadoRuta.DESCARGADA -> coloresMilkFlow.info
                        else -> coloresMilkFlow.aviso
                    },
                )
            }
        }

        if (abierta) {
            Spacer(Modifier.height(12.dp))

            if (recepcion != null) {
                FilaDato("Caudalímetro en planta", recepcion.litrosCaudalimetro.litros())
                FilaDato(
                    "Diferencia",
                    recepcion.diferencia.litros(),
                    color = if (recepcion.diferencia < 0) coloresMilkFlow.peligro else coloresMilkFlow.exito,
                )
                if (!recepcion.observation.isNullOrBlank()) {
                    Spacer(Modifier.height(8.dp))
                    Nota("Jefe de planta: ${recepcion.observation}", coloresMilkFlow.info, "📝")
                }
            } else {
                Nota("Pendiente de verificación con caudalímetro.", coloresMilkFlow.aviso, "⏳")
            }

            Spacer(Modifier.height(10.dp))
            Text("Entregas (${entregas.size})", style = MaterialTheme.typography.labelLarge)
            Spacer(Modifier.height(4.dp))

            entregas.sortedBy { estado.usuario(it.productorId)?.name }.forEach { entrega ->
                FilaDato(
                    estado.usuario(entrega.productorId)?.name ?: "Proveedor",
                    entrega.liters.litros(),
                )
            }
        }
    }
}
