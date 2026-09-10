@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.productor

import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.domain.model.EstadoRecepcion
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.ChipEstado
import pe.edu.upeu.milkflow.ui.components.IndicadorSync
import pe.edu.upeu.milkflow.ui.components.SeccionTarjeta
import pe.edu.upeu.milkflow.ui.components.TarjetaKpi
import pe.edu.upeu.milkflow.ui.components.TonoChip
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.litros
import pe.edu.upeu.milkflow.ui.util.soles

@Composable
fun ProductorScreen(
    sesion: SesionActiva,
    onAbrirConflictos: () -> Unit,
    onCerrarSesion: () -> Unit,
    vm: ProductorViewModel = koinViewModel { parametersOf(sesion) },
) {
    val s by vm.estado.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Hola, ${s.nombre.ifBlank { "productor" }}") },
                actions = {
                    TextButton(onClick = onCerrarSesion, modifier = Modifier.objetivoTactil()) { Text("Salir") }
                },
            )
        },
    ) { pad ->
        Column(
            Modifier.fillMaxSize().padding(pad).padding(Dimens.EspacioM).verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            IndicadorSync(onAbrirConflictos = onAbrirConflictos)

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                TarjetaKpi("Litros entregados hoy", litros(s.litrosHoy), Modifier.weight(1f))
                Column(Modifier.weight(1f)) {
                    SeccionTarjeta("Estado de calidad") {
                        ChipEstado(
                            s.estadoCalidad,
                            if (s.calidadRechaza) TonoChip.ALERTA else TonoChip.CONFORME,
                        )
                    }
                }
            }

            SeccionTarjeta("Ciclo de pago (jueves → miércoles)") {
                Text("Acumulado del ciclo: ${litros(s.litrosCiclo)}")
                Text("Tarifa vigente: ${soles(s.tarifaLitro)} por litro")
                Text(
                    "Pago proyectado (viernes): ${soles(s.pagoProyectado)}",
                    fontWeight = FontWeight.Bold,
                    style = MaterialTheme.typography.titleMedium,
                )
                if (s.ultimaLiquidacionNeto != null) {
                    Text("Última liquidación pagada: ${soles(s.ultimaLiquidacionNeto!!)}", color = LocalColoresMilkFlow.current.tintaSuave)
                }
                if (s.litrosFaltantesCiclo > 0.0) {
                    ChipEstado("Faltó ${litros(s.litrosFaltantesCiclo)} en el ciclo (según planta)", TonoChip.ALERTA)
                }
            }

            SeccionTarjeta("Mis entregas y su recepción en planta") {
                if (s.entregas.isEmpty()) {
                    Text("Aún no hay entregas registradas.", color = LocalColoresMilkFlow.current.tintaSuave)
                }
                s.entregas.forEach { e ->
                    Row(
                        Modifier.fillMaxWidth().padding(vertical = 2.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                    ) {
                        Text("${e.fecha} · ${litros(e.litros)}")
                        val (texto, tono) = when (e.estadoRecepcion) {
                            EstadoRecepcion.CONFORME -> "Conforme" to TonoChip.CONFORME
                            EstadoRecepcion.FALTANTE -> "Faltó ${litros(e.litrosFaltantes)}" to TonoChip.ALERTA
                            EstadoRecepcion.EXCEDENTE -> "Excedente" to TonoChip.ACCION
                            EstadoRecepcion.PENDIENTE -> "Por verificar" to TonoChip.NEUTRO
                        }
                        ChipEstado(texto, tono)
                    }
                }
            }

            SeccionTarjeta("Ruta asignada") {
                Text(s.rutaAsignada, style = MaterialTheme.typography.titleMedium)
                s.estadoUltimaSolicitud?.let {
                    ChipEstado(
                        "Última solicitud: ${it.clave}",
                        when (it.clave) {
                            "aprobada" -> TonoChip.CONFORME
                            "rechazada" -> TonoChip.ALERTA
                            else -> TonoChip.NEUTRO
                        },
                    )
                }
                Button(
                    onClick = vm::abrirSolicitud,
                    colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
                    modifier = Modifier.fillMaxWidth().objetivoTactil(),
                ) { Text("Solicitar cambio de ruta") }
            }
        }
    }

    // --- Pop-up obligatorio del aviso vigente ---
    s.aviso?.let { aviso ->
        AlertDialog(
            onDismissRequest = { /* obligatorio: no se descarta tocando fuera */ },
            title = { Text(aviso.titulo) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                    s.avisoImagen?.let { img ->
                        Image(
                            bitmap = img,
                            contentDescription = aviso.titulo,
                            contentScale = ContentScale.FillWidth,
                            modifier = Modifier.fillMaxWidth().heightIn(max = 220.dp),
                        )
                    }
                    Text(aviso.contenido, style = MaterialTheme.typography.bodyLarge)
                }
            },
            confirmButton = {
                Button(onClick = vm::confirmarAviso, modifier = Modifier.objetivoTactil()) {
                    Text("Leído y Entendido")
                }
            },
        )
    }

    // --- Sheet de solicitud de cambio ---
    s.sheet?.let { sheet ->
        val sheetState = rememberModalBottomSheetState()
        ModalBottomSheet(onDismissRequest = vm::cerrarSolicitud, sheetState = sheetState) {
            Column(
                Modifier.fillMaxWidth().padding(Dimens.EspacioL),
                verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
            ) {
                Text("Solicitar cambio de ruta / zona", style = MaterialTheme.typography.titleMedium)
                Text("Elija la zona a la que quiere pasar:", style = MaterialTheme.typography.bodyMedium)
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    s.zonas.filter { it.id != s.zonaActualId }.forEach { zona ->
                        FilterChip(
                            selected = sheet.zonaSeleccionadaId == zona.id,
                            onClick = { vm.onZonaSolicitud(zona.id) },
                            label = { Text(zona.nombre) },
                            modifier = Modifier.objetivoTactil(),
                        )
                    }
                }
                OutlinedTextField(
                    value = sheet.motivo,
                    onValueChange = vm::onMotivo,
                    label = { Text("Motivo") },
                    modifier = Modifier.fillMaxWidth().heightIn(min = 96.dp),
                )
                if (sheet.error != null) {
                    Text(sheet.error, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodyMedium)
                }
                Button(
                    onClick = vm::enviarSolicitud,
                    enabled = !sheet.guardando,
                    colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
                    modifier = Modifier.fillMaxWidth().objetivoTactil(),
                ) { Text("Enviar solicitud") }
            }
        }
    }
}
