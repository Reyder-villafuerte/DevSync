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
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.foundation.text.KeyboardOptions
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.TarjetaKpi
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.litros

@Composable
fun CierreRutaScreen(
    sesion: SesionActiva,
    onVolver: () -> Unit,
    onComprobante: (String) -> Unit,
    vm: CierreRutaViewModel = koinViewModel { parametersOf(sesion) },
) {
    val s by vm.estado.collectAsState()

    // La navegación al comprobante se dispara desde el estado (efecto de una sola vez).
    LaunchedEffect(s.cerrada) {
        s.cerrada?.let(onComprobante)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Cierre de ruta") },
                navigationIcon = {
                    TextButton(onClick = onVolver, modifier = Modifier.objetivoTactil()) { Text("Volver") }
                },
            )
        },
    ) { pad ->
        Column(
            Modifier.fillMaxSize().padding(pad).padding(Dimens.EspacioM).verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            if (!s.hayJornada) {
                Text("No hay una ruta abierta para cerrar.", style = MaterialTheme.typography.bodyLarge)
                return@Column
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                TarjetaKpi("Litros", litros(s.litros), Modifier.weight(1f))
                TarjetaKpi("Socios", "${s.socios}", Modifier.weight(1f))
                TarjetaKpi("Pendientes", "${s.paradasPendientes}", Modifier.weight(1f))
            }

            if (s.paradasPendientes > 0) {
                Text(
                    "Quedan ${s.paradasPendientes} parada(s) sin recolección. Aun así puede cerrar la ruta.",
                    color = LocalColoresMilkFlow.current.accion,
                    style = MaterialTheme.typography.bodyMedium,
                )
            }

            Text("Descarga en tina (opcional)", style = MaterialTheme.typography.titleMedium)
            OutlinedTextField(
                value = s.tina, onValueChange = vm::onTina, label = { Text("Tina") },
                singleLine = true, modifier = Modifier.fillMaxWidth().objetivoTactil(),
            )
            OutlinedTextField(
                value = s.litrosDescargados, onValueChange = vm::onLitrosDescargados,
                label = { Text("Litros descargados") }, singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            )
            OutlinedTextField(
                value = s.recibidoPor, onValueChange = vm::onRecibidoPor, label = { Text("Recibido por") },
                singleLine = true, modifier = Modifier.fillMaxWidth().objetivoTactil(),
            )

            if (s.error != null) {
                Text(s.error!!, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodyMedium)
            }

            Button(
                onClick = vm::confirmarCierre,
                enabled = !s.cerrando,
                colors = ButtonDefaults.buttonColors(containerColor = LocalColoresMilkFlow.current.accion),
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            ) { Text(if (s.cerrando) "Cerrando…" else "Confirmar cierre de ruta") }
        }
    }
}
