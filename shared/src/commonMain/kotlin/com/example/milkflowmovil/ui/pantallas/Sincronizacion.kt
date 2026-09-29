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
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
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
 * Estado de la sincronización: qué falta subir, qué rechazó el servidor y
 * contra qué servidor está trabajando el teléfono.
 *
 * Es la pantalla que convierte el trabajo sin señal en algo visible: el
 * acopiador puede comprobar que nada se perdió.
 */
@Composable
fun PantallaSincronizacion(repositorio: Repositorio, estado: EstadoLocal) {
    val sync by repositorio.estadoSync.collectAsState()
    var servidor by remember { mutableStateOf(estado.urlBase) }
    var editandoServidor by remember { mutableStateOf(false) }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Sin subir",
                    "${estado.cola.size}",
                    "operaciones en cola",
                    if (estado.cola.isEmpty()) coloresMilkFlow.exito else coloresMilkFlow.aviso,
                    Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Rechazadas",
                    "${estado.rechazadas.size}",
                    "requieren tu atención",
                    if (estado.rechazadas.isEmpty()) null else coloresMilkFlow.peligro,
                    Modifier.weight(1f),
                )
            }
        }

        item {
            Tarjeta {
                FilaDato(
                    "Última sincronización",
                    estado.ultimaSincronizacion?.let {
                        "${Fechas.corta(it)} ${Fechas.horaCorta(it)}"
                    } ?: "Nunca",
                )
                FilaDato("Servidor", estado.urlBase)
                FilaDato("Dispositivo", estado.sesion?.dispositivoId ?: "—")

                if (estado.ultimoErrorSync != null) {
                    Spacer(Modifier.height(10.dp))
                    Nota(estado.ultimoErrorSync, coloresMilkFlow.aviso, "📶")
                }

                Spacer(Modifier.height(12.dp))
                BotonPrimario(
                    if (sync.sincronizando) "Sincronizando…" else "Sincronizar ahora",
                    Modifier.fillMaxWidth(),
                    cargando = sync.sincronizando,
                ) { repositorio.sincronizarEnSegundoPlano() }

                Spacer(Modifier.height(8.dp))
                BotonSecundario(
                    if (editandoServidor) "Cancelar" else "Cambiar servidor",
                    Modifier.fillMaxWidth(),
                ) { editandoServidor = !editandoServidor }

                if (editandoServidor) {
                    Spacer(Modifier.height(10.dp))
                    Campo(
                        servidor,
                        "Dirección del servidor",
                        { servidor = it },
                        apoyo = "Emulador: http://10.0.2.2:8000 · Teléfono: la IP de la planta en la red local.",
                    )
                    Spacer(Modifier.height(8.dp))
                    BotonPrimario("Guardar", Modifier.fillMaxWidth()) {
                        repositorio.cambiarServidor(servidor)
                        editandoServidor = false
                    }
                }
            }
        }

        if (estado.cola.isNotEmpty()) {
            item { EncabezadoSeccion("Esperando señal", "Se suben solas en cuanto haya internet.") }

            items(estado.cola, key = { it.clientUuid }) { operacion ->
                Tarjeta {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text(operacion.descripcion, style = MaterialTheme.typography.bodyMedium)
                            Text(
                                "${Fechas.corta(operacion.creadaEn)} ${Fechas.horaCorta(operacion.creadaEn)}" +
                                    if (operacion.intentos > 0) " · ${operacion.intentos} intentos" else "",
                                style = MaterialTheme.typography.bodySmall,
                                color = coloresMilkFlow.textoSuave,
                            )
                        }
                        Etiqueta("En cola", coloresMilkFlow.aviso)
                    }
                }
            }
        }

        if (estado.rechazadas.isNotEmpty()) {
            item {
                EncabezadoSeccion(
                    "Rechazadas por la planta",
                    "El servidor no pudo aplicarlas. Revísalas y vuelve a registrarlas si corresponde.",
                )
            }

            items(estado.rechazadas, key = { "r-${it.clientUuid}" }) { rechazada ->
                Tarjeta {
                    Text(rechazada.descripcion, fontWeight = FontWeight.Bold)
                    Spacer(Modifier.height(8.dp))
                    Nota(rechazada.motivo, coloresMilkFlow.peligro, "⚠️")
                    Spacer(Modifier.height(10.dp))
                    BotonSecundario("Entendido, quitar del listado", Modifier.fillMaxWidth()) {
                        repositorio.descartarRechazada(rechazada.clientUuid)
                    }
                }
            }
        }

        if (estado.cola.isEmpty() && estado.rechazadas.isEmpty()) {
            item { MensajeVacio("Todo está sincronizado con la planta.", "✅") }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}
