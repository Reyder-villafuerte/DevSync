@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.acopiador

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
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
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
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.ui.components.ContenedorEstado
import pe.edu.upeu.milkflow.ui.components.IndicadorSync
import pe.edu.upeu.milkflow.ui.components.SeccionTarjeta
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.Impresion
import pe.edu.upeu.milkflow.ui.util.litros

@Composable
fun ComprobanteScreen(
    jornadaId: String,
    onVolver: () -> Unit,
    onAbrirConflictos: () -> Unit,
    vm: ComprobanteViewModel = koinViewModel { parametersOf(jornadaId) },
) {
    val estado by vm.estado.collectAsState()
    val context = LocalContext.current

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Comprobante de cierre") },
                navigationIcon = {
                    TextButton(onClick = onVolver, modifier = Modifier.objetivoTactil()) { Text("Inicio") }
                },
            )
        },
    ) { pad ->
        Column(Modifier.fillMaxSize().padding(pad)) {
            ContenedorEstado(estado, onReintentar = vm::cargar) { datos ->
                Column(
                    Modifier.fillMaxSize().padding(Dimens.EspacioM).verticalScroll(rememberScrollState()),
                    verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
                ) {
                    IndicadorSync(onAbrirConflictos = onAbrirConflictos)

                    SeccionTarjeta("Folio ${datos.folio}") {
                        Text("Fecha de cierre: ${datos.fecha}")
                        Text("Estado de la jornada: ${datos.estadoJornada}")
                        HorizontalDivider(Modifier.padding(vertical = Dimens.EspacioS))
                        datos.lineas.forEach { l ->
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text(l.productor)
                                Text(litros(l.litros))
                            }
                        }
                        HorizontalDivider(Modifier.padding(vertical = Dimens.EspacioS))
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("Socios atendidos"); Text("${datos.socios}")
                        }
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("TOTAL", fontWeight = FontWeight.Bold)
                            Text(litros(datos.totalLitros), fontWeight = FontWeight.Bold)
                        }
                    }

                    Button(
                        onClick = { Impresion.imprimir(context, "Comprobante ${datos.folio}", datos.aLineasTexto()) },
                        colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
                        modifier = Modifier.fillMaxWidth().objetivoTactil(),
                    ) { Text("Imprimir comprobante") }
                }
            }
        }
    }
}
