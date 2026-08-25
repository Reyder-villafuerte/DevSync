package pe.edu.upeu.milkflow

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.ui.pantallas.AcopioPantalla
import pe.edu.upeu.milkflow.ui.pantallas.FormularioAcopio
import pe.edu.upeu.milkflow.ui.pantallas.acopiosDemo

@Composable
fun App() {
    // Estado elevado (Actividad 8 de la guía)
    var acopios by remember { mutableStateOf(acopiosDemo) }

    MaterialTheme {
        Scaffold { innerPadding ->
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
            ) {
                // Formulario arriba
                FormularioAcopio(
                    alGuardar = { nuevo ->
                        acopios = acopios + nuevo
                    }
                )
                
                HorizontalDivider()
                
                // Lista abajo
                AcopioPantalla(
                    acopios = acopios,
                    modifier = Modifier.weight(1f)
                )
            }
        }
    }
}
