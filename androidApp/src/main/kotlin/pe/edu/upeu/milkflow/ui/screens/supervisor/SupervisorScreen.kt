@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.supervisor

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.foundation.text.KeyboardOptions
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.domain.model.DictamenCalidad
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.ChipEstado
import pe.edu.upeu.milkflow.ui.components.DialogoConfirmacion
import pe.edu.upeu.milkflow.ui.components.IndicadorSync
import pe.edu.upeu.milkflow.ui.components.SeccionTarjeta
import pe.edu.upeu.milkflow.ui.components.TonoChip
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.Impresion
import pe.edu.upeu.milkflow.ui.util.presentacionDe
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.unit.dp

@Composable
fun SupervisorScreen(
    sesion: SesionActiva,
    onAbrirHistorial: () -> Unit,
    onAbrirConflictos: () -> Unit,
    onCerrarSesion: () -> Unit,
    vm: InspeccionViewModel = koinViewModel { parametersOf(sesion) },
) {
    val s by vm.estado.collectAsState()
    val context = LocalContext.current
    var confirmando by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Supervisión de calidad") },
                actions = {
                    TextButton(onClick = onAbrirHistorial, modifier = Modifier.objetivoTactil()) { Text("Historial") }
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

            SeccionTarjeta("Ruta") {
                Row(horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                    listOf("01", "02").forEach { codigo ->
                        FilterChip(
                            selected = s.rutaSeleccionada == codigo,
                            onClick = { vm.onRuta(codigo) },
                            label = { Text("Ruta $codigo") },
                            modifier = Modifier.objetivoTactil(),
                        )
                    }
                }
            }

            if (s.rutaSeleccionada != null && s.productorSeleccionado == null) {
                SeccionTarjeta("Productores de la ruta") {
                    if (s.productores.isEmpty()) {
                        Text("Sin productores sincronizados para esta ruta.", color = LocalColoresMilkFlow.current.tintaSuave)
                    }
                    s.productores.forEach { p ->
                        OutlinedButton(
                            onClick = { vm.onProductor(p) },
                            modifier = Modifier.fillMaxWidth().objetivoTactil().padding(vertical = 2.dp),
                        ) { Text("${p.nombre}  ·  ${p.codigoPadron}") }
                    }
                }
            }

            s.productorSeleccionado?.let { prod ->
                SeccionTarjeta("Lactoscan — ${prod.nombre}") {
                    CampoNumero("pH", s.form.ph, vm::onPh)
                    CampoNumero("Agua adulterada (%)", s.form.agua, vm::onAgua)
                    CampoNumero("Densidad", s.form.densidad, vm::onDensidad)
                    CampoNumero("Temperatura (°C)", s.form.temperatura, vm::onTemperatura)

                    if (s.errorForm != null) {
                        Text(s.errorForm!!, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodyMedium)
                    }

                    Button(
                        onClick = vm::previsualizar,
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                        modifier = Modifier.fillMaxWidth().objetivoTactil(),
                    ) { Text("Registrar / evaluar") }
                    TextButton(onClick = vm::reiniciar, modifier = Modifier.objetivoTactil()) { Text("Elegir otro productor") }
                }

                s.preview?.let { veredicto ->
                    TarjetaDictamen(
                        veredicto = veredicto,
                        registrando = s.registrando,
                        registrada = s.medidaRegistrada,
                        onEjecutar = { confirmando = true },
                        onImprimir = {
                            s.inspeccionParaImprimir?.let { Impresion.imprimir(context, "Ticket de inspección", it) }
                        },
                    )
                }
            }
        }
    }

    if (confirmando) {
        val etiqueta = s.preview?.dictamen?.let { presentacionDe(it).etiquetaAccion } ?: "Confirmar"
        DialogoConfirmacion(
            titulo = "Confirmar medida",
            mensaje = "Se registrará la inspección con el dictamen «${s.preview?.dictamen?.clave}» y la medida quedará encolada para su aplicación.",
            textoConfirmar = etiqueta,
            onConfirmar = { confirmando = false; vm.ejecutar() },
            onCancelar = { confirmando = false },
        )
    }
}

@Composable
private fun CampoNumero(etiqueta: String, valor: String, onCambio: (String) -> Unit) {
    OutlinedTextField(
        value = valor,
        onValueChange = onCambio,
        label = { Text(etiqueta) },
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
        modifier = Modifier.fillMaxWidth().objetivoTactil().padding(vertical = 2.dp),
    )
}

@Composable
private fun TarjetaDictamen(
    veredicto: pe.edu.upeu.milkflow.domain.calidad.EvaluadorCalidad.Resultado,
    registrando: Boolean,
    registrada: Boolean,
    onEjecutar: () -> Unit,
    onImprimir: () -> Unit,
) {
    val p = presentacionDe(veredicto.dictamen)
    val c = LocalColoresMilkFlow.current
    Card(
        shape = Dimens.FormaTarjeta,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(Dimens.EspacioM), verticalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
            Text(p.titular, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            ChipEstado(
                if (veredicto.rechazaLote) "LOTE RECHAZADO" else "LOTE ACEPTADO",
                if (veredicto.rechazaLote) TonoChip.ALERTA else TonoChip.CONFORME,
            )
            Text(veredicto.detalle, style = MaterialTheme.typography.bodyMedium)
            Text("Tarifa: ${p.tarifaResultante}")
            Text("Padrón: ${p.situacionPadron}")
            if (veredicto.esReincidencia) {
                Text("Reincidencia registrada", color = c.alerta, fontWeight = FontWeight.Bold)
            }

            if (registrada) {
                Text("Medida registrada y encolada; el backend la aplicará al sincronizar.", color = c.conforme, fontWeight = FontWeight.SemiBold)
                Button(
                    onClick = onImprimir,
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                    modifier = Modifier.fillMaxWidth().objetivoTactil(),
                ) { Text("Imprimir ticket térmico") }
            } else {
                Button(
                    onClick = onEjecutar,
                    enabled = !registrando,
                    colors = ButtonDefaults.buttonColors(containerColor = c.accion),
                    modifier = Modifier.fillMaxWidth().objetivoTactil(),
                ) { Text(if (registrando) "Registrando…" else p.etiquetaAccion) }
            }
        }
    }
}
