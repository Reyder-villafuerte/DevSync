package pe.edu.upeu.milkflow.ui.pantallas

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.domain.model.Acopio
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion

@Composable
fun AcopioPantalla(
    acopios: List<Acopio>,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.fillMaxSize().padding(16.dp)) {
        Text(
            text = "Acopios registrados (${acopios.size})",
            style = MaterialTheme.typography.headlineSmall,
            modifier = Modifier.padding(bottom = 8.dp)
        )
        
        LazyColumn(
            contentPadding = PaddingValues(vertical = 8.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            items(acopios, key = { it.id }) { acopio ->
                TarjetaAcopio(acopio = acopio)
            }
        }
    }
}

// Datos demo para previsualización o estado inicial
val acopiosDemo = listOf(
    Acopio("1", 1724620000000L, "Prod-001", 15.5, 2.5, "Buena", EstadoSincronizacion.SINCRONIZADO),
    Acopio("2", 1724620000000L, "Prod-002", 20.0, 2.4, null, EstadoSincronizacion.PENDIENTE)
)
