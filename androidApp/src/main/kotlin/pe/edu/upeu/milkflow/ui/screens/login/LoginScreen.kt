package pe.edu.upeu.milkflow.ui.screens.login

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.foundation.text.KeyboardOptions
import org.koin.androidx.compose.koinViewModel
import pe.edu.upeu.milkflow.config.ConfiguracionServidor
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow

@Composable
fun LoginScreen(vm: LoginViewModel = koinViewModel()) {
    val s by vm.estado.collectAsState()
    val colores = LocalColoresMilkFlow.current
    val context = LocalContext.current

    var mostrarServidor by rememberSaveable { mutableStateOf(false) }
    var urlServidor by remember { mutableStateOf(ConfiguracionServidor.leer(context)) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(Dimens.EspacioL),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM, Alignment.CenterVertically),
    ) {
        Text("MilkFlow", style = MaterialTheme.typography.headlineSmall, color = MaterialTheme.colorScheme.primary)
        Text(
            "Ingrese con su DNI y contraseña",
            style = MaterialTheme.typography.bodyMedium,
            color = colores.tintaSuave,
        )

        OutlinedTextField(
            value = s.dni,
            onValueChange = vm::onDni,
            label = { Text("DNI") },
            singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
            modifier = Modifier.fillMaxWidth().objetivoTactil(),
        )
        OutlinedTextField(
            value = s.password,
            onValueChange = vm::onPassword,
            label = { Text("Contraseña") },
            singleLine = true,
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
            modifier = Modifier.fillMaxWidth().objetivoTactil(),
        )

        if (s.error != null) {
            Text(
                s.error!!,
                color = MaterialTheme.colorScheme.error,
                style = MaterialTheme.typography.bodyMedium,
                textAlign = TextAlign.Center,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        Button(
            onClick = vm::ingresar,
            enabled = s.puedeIngresar,
            colors = ButtonDefaults.buttonColors(containerColor = colores.accion),
            modifier = Modifier.fillMaxWidth().objetivoTactil(),
        ) {
            if (s.cargando) {
                CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp, color = MaterialTheme.colorScheme.onPrimary)
            } else {
                Text("Ingresar", style = MaterialTheme.typography.labelLarge)
            }
        }

        // --- Configuración del servidor (para desarrollo / demo en distintos dispositivos) ---
        TextButton(onClick = { mostrarServidor = !mostrarServidor }, modifier = Modifier.objetivoTactil()) {
            Text(if (mostrarServidor) "Ocultar servidor" else "Configurar servidor")
        }
        if (mostrarServidor) {
            OutlinedTextField(
                value = urlServidor,
                onValueChange = { urlServidor = it },
                label = { Text("URL del backend") },
                singleLine = true,
                supportingText = {
                    Text("Emulador: http://10.0.2.2:8000/  ·  adb reverse: http://127.0.0.1:8000/  ·  celular: http://IP-DE-TU-PC:8000/")
                },
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            )
            OutlinedButton(
                onClick = { ConfiguracionServidor.guardarYReiniciar(context, urlServidor) },
                modifier = Modifier.fillMaxWidth().objetivoTactil(),
            ) { Text("Guardar y reiniciar app") }
        }
    }
}
