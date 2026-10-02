package com.example.milkflowmovil.presentation.screens

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
import androidx.compose.runtime.collectAsState
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
import com.example.milkflowmovil.domain.model.EstadoApp
import com.example.milkflowmovil.domain.model.EstadoRuta
import com.example.milkflowmovil.domain.model.Ruta
import com.example.milkflowmovil.domain.rules.Reglas
import com.example.milkflowmovil.presentation.components.BotonPrimario
import com.example.milkflowmovil.presentation.components.Campo
import com.example.milkflowmovil.presentation.components.Etiqueta
import com.example.milkflowmovil.presentation.components.FilaDato
import com.example.milkflowmovil.presentation.components.MensajeVacio
import com.example.milkflowmovil.presentation.components.Nota
import com.example.milkflowmovil.presentation.components.Tarjeta
import com.example.milkflowmovil.presentation.components.TarjetaMetrica
import com.example.milkflowmovil.presentation.theme.coloresMilkFlow
import com.example.milkflowmovil.presentation.viewmodel.PlantaViewModel

/**
 * Verificación con caudalímetro.
 *
 * Regla que sostiene todo el inventario: al stock entra únicamente lo que midió
 * el caudalímetro en planta, nunca lo que declaró el acopiador en la ruta.
 */
@Composable
fun PantallaCaudalimetro(viewModel: PlantaViewModel) {
    val estado = viewModel.uiState.collectAsState().value
    val hoy = Fechas.hoy()
    var soloHoy by remember { mutableStateOf(true) }

    val rutas = estado.rutas
        .filter { !soloHoy || it.date == hoy }
        .sortedWith(compareByDescending<Ruta> { it.date }.thenBy { it.zonaId })

    val esperando = rutas.count { it.estado == EstadoRuta.DESCARGADA }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Stock de leche",
                    estado.stockLeche.litros(),
                    "verificada en planta",
                    coloresMilkFlow.acento,
                    Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Esperando",
                    "$esperando",
                    "rutas descargadas",
                    if (esperando > 0) coloresMilkFlow.aviso else null,
                    Modifier.weight(1f),
                )
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(soloHoy, { soloHoy = true }, { Text("Hoy") })
                FilterChip(!soloHoy, { soloHoy = false }, { Text("Todas") })
            }
        }

        if (rutas.isEmpty()) {
            item { MensajeVacio("No hay rutas para verificar.", "📏") }
        }

        items(rutas, key = { it.id }) { ruta ->
            TarjetaVerificacion(viewModel, estado, ruta)
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

@Composable
private fun TarjetaVerificacion(viewModel: PlantaViewModel, estado: EstadoApp, ruta: Ruta) {
    val recepcion = estado.recepcionDeRuta(ruta.id)
    val acopiador = estado.usuario(ruta.acopiadorId)

    var abierta by remember(ruta.id) { mutableStateOf(ruta.estado == EstadoRuta.DESCARGADA) }
    var caudalimetro by remember(ruta.id) {
        mutableStateOf(recepcion?.litrosCaudalimetro?.toString()?.removeSuffix(".0") ?: "")
    }
    var observacion by remember(ruta.id) { mutableStateOf(recepcion?.observation ?: "") }
    var error by remember(ruta.id) { mutableStateOf<String?>(null) }

    val medido = caudalimetro.toDoubleOrNull()
    val diferencia = medido?.let { Reglas.merma(ruta.litrosTotales, it) }

    Tarjeta(alPulsar = { abierta = !abierta }) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(
                    estado.zona(ruta.zonaId)?.name ?: "Zona",
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    "${acopiador?.name ?: "Acopiador"} · ${Fechas.corta(ruta.date)}",
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

        if (!abierta) return@Tarjeta

        Spacer(Modifier.height(12.dp))
        FilaDato("Declarado por el acopiador", ruta.litrosTotales.litros())
        FilaDato("Entregas registradas", "${estado.entregasDeRuta(ruta.id).size}")

        if (recepcion != null) {
            FilaDato("Medición anterior", recepcion.litrosCaudalimetro.litros())
        }

        Spacer(Modifier.height(12.dp))
        Campo(caudalimetro, "Litros del caudalímetro", { caudalimetro = it; error = null }, decimal = true)

        if (diferencia != null) {
            Spacer(Modifier.height(8.dp))
            Nota(
                when {
                    diferencia < 0 -> "Faltan ${(-diferencia).litros()} respecto a lo declarado en ruta."
                    diferencia > 0 -> "Hay ${diferencia.litros()} de más respecto a lo declarado."
                    else -> "La medición cuadra exactamente con lo declarado."
                },
                when {
                    diferencia < 0 -> coloresMilkFlow.peligro
                    diferencia > 0 -> coloresMilkFlow.aviso
                    else -> coloresMilkFlow.exito
                },
                "⚖️",
            )
        }

        Spacer(Modifier.height(8.dp))
        Campo(observacion, "Observación para el acopiador", { observacion = it }, lineas = 2)

        if (error != null) {
            Spacer(Modifier.height(8.dp))
            Nota(error!!, coloresMilkFlow.peligro, "⚠️")
        }

        Spacer(Modifier.height(12.dp))
        BotonPrimario(
            if (recepcion == null) "Confirmar recepción" else "Corregir medición",
            Modifier.fillMaxWidth(),
        ) {
            val valor = caudalimetro.toDoubleOrNull()
            if (valor == null || valor < 0) {
                error = "Escribe los litros que marcó el caudalímetro."
            } else {
                viewModel.verificarRecepcion(
                    ruta = ruta,
                    litrosCaudalimetro = valor,
                    estadoVerificacion = Reglas.estadoSugerido(ruta.litrosTotales, valor),
                    observacion = observacion.ifBlank { null },
                )
                abierta = false
            }
        }
    }
}
