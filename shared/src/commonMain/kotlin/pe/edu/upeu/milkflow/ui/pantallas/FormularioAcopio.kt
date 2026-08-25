package pe.edu.upeu.milkflow.ui.pantallas

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.domain.model.Acopio
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion

@Composable
fun FormularioAcopio(
    alGuardar: (Acopio) -> Unit,
    modifier: Modifier = Modifier
) {
    var productorId by remember { mutableStateOf("") }
    var litrosTexto by remember { mutableStateOf("") }
    var precioTexto by remember { mutableStateOf("") }
    var notaCalidad by remember { mutableStateOf("") }

    // Validaciones derivadas
    val litros = litrosTexto.toDoubleOrNull()
    val precio = precioTexto.toDoubleOrNull()
    
    val formularioValido = productorId.isNotBlank() && 
            litros != null && litros > 0 && 
            precio != null && precio >= 0

    Column(
        modifier = modifier.fillMaxWidth().padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Text(text = "Nuevo Registro de Acopio", style = MaterialTheme.typography.titleLarge)

        OutlinedTextField(
            value = productorId,
            onValueChange = { productorId = it },
            label = { Text("ID Productor") },
            modifier = Modifier.fillMaxWidth()
        )

        OutlinedTextField(
            value = litrosTexto,
            onValueChange = { litrosTexto = it },
            label = { Text("Litros") },
            modifier = Modifier.fillMaxWidth()
        )

        OutlinedTextField(
            value = precioTexto,
            onValueChange = { precioTexto = it },
            label = { Text("Precio por Litro") },
            modifier = Modifier.fillMaxWidth()
        )

        OutlinedTextField(
            value = notaCalidad,
            onValueChange = { notaCalidad = it },
            label = { Text("Observaciones de Calidad") },
            modifier = Modifier.fillMaxWidth()
        )

        Button(
            onClick = {
                val nuevo = Acopio(
                    id = productorId + litros.toString(), // ID simple para el ejercicio
                    fecha = 0L, // En la guía no se suele manejar fechas complejas aún
                    productorId = productorId,
                    litros = litros ?: 0.0,
                    precioAplicado = precio ?: 0.0,
                    notaCalidad = if (notaCalidad.isBlank()) null else notaCalidad,
                    estadoSinc = EstadoSincronizacion.PENDIENTE
                )
                alGuardar(nuevo)
                
                // Limpiar campos
                productorId = ""
                litrosTexto = ""
                precioTexto = ""
                notaCalidad = ""
            },
            enabled = formularioValido,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text("Guardar acopio")
        }
    }
}
