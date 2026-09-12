@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.conflictos

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import org.koin.androidx.compose.koinViewModel
import pe.edu.upeu.milkflow.ui.components.BotonPrincipal
import pe.edu.upeu.milkflow.ui.components.ChipEstado
import pe.edu.upeu.milkflow.ui.components.ContenedorEstado
import pe.edu.upeu.milkflow.ui.components.DialogoConfirmacion
import pe.edu.upeu.milkflow.ui.components.Rotulo
import pe.edu.upeu.milkflow.ui.components.TarjetaMilkFlow
import pe.edu.upeu.milkflow.ui.components.TonoChip
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow

@Composable
fun ConflictosScreen(
    onVolver: () -> Unit,
    vm: ConflictosViewModel = koinViewModel(),
) {
    val estado by vm.estado.collectAsState()
    val reintentando by vm.reintentandoFlow.collectAsState()
    var aDescartar by remember { mutableStateOf<ConflictoItem?>(null) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Conflictos de sincronización") },
                navigationIcon = {
                    TextButton(onClick = onVolver, modifier = Modifier.objetivoTactil()) { Text("Volver") }
                },
            )
        },
    ) { pad ->
        Column(Modifier.fillMaxSize().padding(pad)) {
            ContenedorEstado(estado) { lista ->
                LazyColumn(
                    contentPadding = PaddingValues(Dimens.EspacioM),
                    verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
                ) {
                    item {
                        Text(
                            "Estos envíos no los aceptó el servidor. Reintentar solo ayuda si " +
                                "la causa ya se corrigió; si no, resuélvalos desde el panel web.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = LocalColoresMilkFlow.current.tintaSuave,
                        )
                    }
                    items(lista, key = { it.id }) { c ->
                        TarjetaConflicto(c) { aDescartar = c }
                    }
                    item {
                        BotonPrincipal(
                            texto = if (reintentando) "Sincronizando…" else "Reintentar sincronización",
                            onClick = vm::reintentar,
                            habilitado = !reintentando,
                            anchoCompleto = true,
                            modifier = Modifier.padding(top = Dimens.EspacioS),
                        )
                    }
                }
            }
        }
    }

    // Descartar es irreversible para el envío: siempre se confirma.
    aDescartar?.let { c ->
        DialogoConfirmacion(
            titulo = "¿Descartar este envío?",
            mensaje = "${nombreDeEntidad(c.entidad)} del ${c.cuando}. Dejará de intentar " +
                "subirse y desaparecerá de esta lista. El servidor nunca lo recibirá: " +
                "si el dato hace falta, hay que registrarlo desde el panel web.",
            textoConfirmar = "Descartar",
            onConfirmar = { vm.descartar(c.id); aDescartar = null },
            onCancelar = { aDescartar = null },
        )
    }
}

@Composable
private fun TarjetaConflicto(c: ConflictoItem, onDescartar: () -> Unit) {
    val colores = LocalColoresMilkFlow.current
    TarjetaMilkFlow(Modifier.fillMaxWidth()) {
        Column(
            Modifier.padding(Dimens.EspacioM),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS),
        ) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Text(
                    nombreDeEntidad(c.entidad),
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.weight(1f),
                )
                ChipEstado(nombreDeOperacion(c.operacion), TonoChip.NEUTRO)
            }
            Text(
                explicarMotivo(c.motivo),
                style = MaterialTheme.typography.bodyLarge,
                color = colores.alerta,
            )
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Rotulo("${c.cuando} · ${c.idRegistro.take(8)}", Modifier.weight(1f))
                TextButton(onClick = onDescartar, modifier = Modifier.objetivoTactil()) {
                    Text("Descartar", color = colores.alerta)
                }
            }
        }
    }
}

/**
 * Traducción del vocabulario del protocolo al del acopiador. En la pantalla no
 * puede aparecer "rutas_acopio · violacion_integridad": no dice qué pasó ni
 * qué hacer.
 */
private fun nombreDeEntidad(entidad: String): String = when (entidad) {
    "rutas_acopio" -> "Ruta del día"
    "registros_acopio" -> "Recolección"
    "controles_calidad" -> "Inspección de calidad"
    "movimientos_stock" -> "Movimiento de almacén"
    "solicitudes_cambio_zona" -> "Solicitud de cambio de zona"
    else -> entidad.replace('_', ' ').replaceFirstChar { it.uppercase() }
}

private fun nombreDeOperacion(operacion: String): String = when (operacion.uppercase()) {
    "INSERTAR" -> "Alta"
    "ACTUALIZAR" -> "Cambio"
    "ELIMINAR" -> "Baja"
    else -> operacion.lowercase().replaceFirstChar { it.uppercase() }
}

private fun explicarMotivo(motivo: String): String = when (motivo) {
    "violacion_integridad" ->
        "El servidor no pudo guardarlo: choca con otro registro o le falta un dato relacionado."
    "jornada_del_dia_ya_existe" ->
        "Ya existe una ruta registrada para ese acopiador y esa fecha."
    "solo_insercion_no_actualizable" ->
        "Esa recolección ya está en el servidor con otro contenido y no se puede modificar."
    "version_desactualizada" ->
        "El registro cambió en el servidor después de que este equipo lo editara."
    "servidor_autoritativo" ->
        "Este dato solo lo cambia la cooperativa desde el panel."
    "entidad_solo_lectura", "entidad_administrada_por_servidor" ->
        "Este equipo no puede crear ni modificar ese tipo de registro."
    "rol_sin_acceso" -> "Su rol no tiene permiso para enviar este registro."
    "uuid_invalido", "entidad_desconocida" ->
        "El envío llegó con un formato que el servidor no reconoce."
    else -> motivo.replace('_', ' ').replaceFirstChar { it.uppercase() }
}
