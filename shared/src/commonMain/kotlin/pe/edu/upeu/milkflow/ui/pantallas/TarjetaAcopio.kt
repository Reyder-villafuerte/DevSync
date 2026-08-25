package pe.edu.upeu.milkflow.ui.pantallas

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.domain.model.Acopio

@Composable
fun TarjetaAcopio(
    acopio: Acopio,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    text = "Productor: ${acopio.productorId}",
                    style = MaterialTheme.typography.titleMedium
                )
                Text(
                    text = "S/ ${acopio.totalPago}",
                    style = MaterialTheme.typography.titleLarge,
                    color = MaterialTheme.colorScheme.primary
                )
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(modifier = Modifier.fillMaxWidth()) {
                Text(text = "Litros: ${acopio.litros}", style = MaterialTheme.typography.bodyMedium)
                Spacer(modifier = Modifier.width(16.dp))
                Text(text = "Precio: S/ ${acopio.precioAplicado}", style = MaterialTheme.typography.bodyMedium)
            }
            
            if (!acopio.notaCalidad.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = "Calidad: ${acopio.notaCalidad}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.secondary
                )
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            Text(
                text = "Estado: ${acopio.estadoSinc}",
                style = MaterialTheme.typography.labelSmall
            )
        }
    }
}
