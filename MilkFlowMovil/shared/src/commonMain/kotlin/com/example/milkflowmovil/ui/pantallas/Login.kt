package com.example.milkflowmovil.ui.pantallas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.ui.componentes.BotonPrimario
import com.example.milkflowmovil.ui.componentes.Campo
import com.example.milkflowmovil.ui.componentes.Nota
import com.example.milkflowmovil.ui.componentes.Tarjeta
import com.example.milkflowmovil.ui.componentes.MarcaHuata
import com.example.milkflowmovil.ui.tema.SelectorTema
import com.example.milkflowmovil.ui.tema.coloresMilkFlow
import kotlinx.coroutines.launch

/**
 * Inicio de sesión con DNI (lo que la gente recuerda) o correo.
 *
 * Incluye el ajuste de la dirección del servidor porque en campo la app puede
 * apuntar a la computadora de la planta y no siempre a la misma red.
 */
@Composable
fun PantallaLogin(repositorio: Repositorio) {
    var usuario by remember { mutableStateOf("") }
    var clave by remember { mutableStateOf("") }
    var cargando by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var mostrarServidor by remember { mutableStateOf(false) }
    var servidor by remember { mutableStateOf(repositorio.actual.urlBase) }

    val alcance = rememberCoroutineScope()

    fun entrar() {
        if (usuario.isBlank() || clave.isBlank()) {
            error = "Escribe tu DNI y tu contraseña."
            return
        }

        cargando = true
        error = null

        alcance.launch {
            val resultado = repositorio.iniciarSesion(usuario, clave)
            cargando = false
            if (resultado is Resultado.Fallo) {
                error = resultado.error.mensaje
            }
        }
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Spacer(Modifier.height(32.dp))

        MarcaHuata()
        SelectorTema()
        Text(
            "Asociación de productores de Huata",
            style = MaterialTheme.typography.bodySmall,
            color = coloresMilkFlow.textoSuave,
            textAlign = TextAlign.Center,
        )

        Spacer(Modifier.height(24.dp))

        Tarjeta(Modifier.fillMaxWidth().widthIn(max = 460.dp)) {
            Text("Ingresar", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(4.dp))
            Text(
                "Puedes entrar con tu DNI o con tu correo.",
                style = MaterialTheme.typography.bodySmall,
                color = coloresMilkFlow.textoSuave,
            )

            Spacer(Modifier.height(16.dp))

            Campo(usuario, "DNI o correo", { usuario = it })

            Spacer(Modifier.height(12.dp))

            OutlinedTextField(
                value = clave,
                onValueChange = { clave = it },
                label = { Text("Contraseña") },
                singleLine = true,
                visualTransformation = PasswordVisualTransformation(),
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password, imeAction = ImeAction.Done),
                modifier = Modifier.fillMaxWidth(),
            )

            if (error != null) {
                Spacer(Modifier.height(12.dp))
                Nota(error!!, coloresMilkFlow.peligro, "⚠️")
            }

            Spacer(Modifier.height(16.dp))

            BotonPrimario(
                texto = if (cargando) "Entrando…" else "Entrar",
                modifier = Modifier.fillMaxWidth(),
                cargando = cargando,
            ) { entrar() }

            Spacer(Modifier.height(8.dp))

            Row(
                Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.Center,
            ) {
                TextButton(onClick = { mostrarServidor = !mostrarServidor }) {
                    Text(
                        if (mostrarServidor) "Ocultar servidor" else "Configurar servidor",
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
            }

            if (mostrarServidor) {
                Campo(
                    servidor,
                    "Dirección del servidor",
                    { servidor = it },
                    apoyo = "Emulador: http://10.0.2.2:8000 · Teléfono en la misma red: la IP de la planta.",
                )
                Spacer(Modifier.height(8.dp))
                BotonPrimario("Guardar dirección", Modifier.fillMaxWidth()) {
                    repositorio.cambiarServidor(servidor)
                    mostrarServidor = false
                }
            }
        }

        Spacer(Modifier.height(20.dp))

        Text(
            "La primera vez necesitas señal para entrar. Después la app funciona sin internet y sincroniza sola.",
            style = MaterialTheme.typography.bodySmall,
            color = coloresMilkFlow.textoSuave,
            textAlign = TextAlign.Center,
            modifier = Modifier.widthIn(max = 420.dp),
        )
    }
}
