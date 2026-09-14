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
import com.example.milkflowmovil.dominio.EstadoRuta
import com.example.milkflowmovil.dominio.Reglas
import com.example.milkflowmovil.dominio.Ruta
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
 * Verificación con caudalímetro.
 *
 * Regla que sostiene todo el inventario: al stock entra únicamente lo que midió
 * el caudalímetro en planta, nunca lo que declaró el acopiador en la ruta.
 */
@Composable
fun PantallaCaudalimetro(repositorio: Repositorio, estado: EstadoLocal) {
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
            TarjetaVerificacion(repositorio, estado, ruta)
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

@Composable
private fun TarjetaVerificacion(repositorio: Repositorio, estado: EstadoLocal, ruta: Ruta) {
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
                repositorio.verificarRecepcion(
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

/** Quesería: cada molde consume 10 litros de leche verificada. */
@Composable
fun PantallaQueseria(repositorio: Repositorio, estado: EstadoLocal) {
    var moldes by remember { mutableStateOf("") }
    var lote by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var exito by remember { mutableStateOf<String?>(null) }

    val posibles = Reglas.moldesPosibles(estado.stockLeche)
    val cantidad = moldes.toIntOrNull() ?: 0

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Leche disponible", estado.stockLeche.litros(), "en planta", modifier = Modifier.weight(1f))
                TarjetaMetrica(
                    "Queso en stock",
                    "${estado.stockQueso.toInt()}",
                    "moldes",
                    coloresMilkFlow.acento,
                    Modifier.weight(1f),
                )
            }
        }

        item {
            Tarjeta {
                Text("Registrar producción", style = MaterialTheme.typography.titleMedium)
                Spacer(Modifier.height(4.dp))
                Text(
                    "Con el stock actual alcanzan $posibles moldes (10 L por molde).",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )

                Spacer(Modifier.height(14.dp))
                Campo(moldes, "Moldes producidos", { moldes = it; error = null; exito = null }, numerico = true)
                Spacer(Modifier.height(8.dp))
                Campo(lote, "Número de lote (opcional)", { lote = it })

                if (cantidad > 0) {
                    Spacer(Modifier.height(10.dp))
                    FilaDato("Leche que se descuenta", Reglas.litrosParaMoldes(cantidad).litros(), resaltado = true)
                }

                if (error != null) {
                    Spacer(Modifier.height(10.dp))
                    Nota(error!!, coloresMilkFlow.peligro, "⚠️")
                }

                if (exito != null) {
                    Spacer(Modifier.height(10.dp))
                    Nota(exito!!, coloresMilkFlow.exito, "✅")
                }

                Spacer(Modifier.height(12.dp))
                BotonPrimario("Registrar producción", Modifier.fillMaxWidth(), habilitado = cantidad > 0) {
                    val fallo = repositorio.producirQueso(cantidad, lote.ifBlank { null })
                    if (fallo != null) {
                        error = fallo.mensaje
                    } else {
                        exito = "Se registraron $cantidad moldes y se descontaron ${Reglas.litrosParaMoldes(cantidad)} L."
                        moldes = ""
                        lote = ""
                    }
                }
            }
        }

        item { EncabezadoSeccion("Producciones recientes") }

        val producciones = estado.producciones.sortedByDescending { it.fecha }

        if (producciones.isEmpty()) {
            item { MensajeVacio("Todavía no se registran producciones.", "🧀") }
        }

        items(producciones, key = { it.id }) { produccion ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text("${produccion.moldes} moldes", fontWeight = FontWeight.Bold)
                        Text(
                            "${Fechas.corta(produccion.fecha)} · ${produccion.lote ?: "sin lote"}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text(produccion.litrosUsados.litros(), style = MaterialTheme.typography.bodySmall)
                        if (produccion.pendiente) {
                            Spacer(Modifier.height(4.dp))
                            Etiqueta("Sin subir", coloresMilkFlow.aviso)
                        }
                    }
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}
