package pe.edu.upeu.milkflow.ui.components

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.ui.theme.Dimens

/**
 * Estados explícitos de pantalla. Cada ViewModel expone su `contenido` como uno
 * de estos; la UI nunca asume "hay datos".
 */
sealed interface EstadoUi<out T> {
    data object Cargando : EstadoUi<Nothing>
    data class Vacio(val mensaje: String) : EstadoUi<Nothing>
    data class Error(val mensaje: String) : EstadoUi<Nothing>
    data class Contenido<T>(val datos: T) : EstadoUi<T>
}

@Composable
fun <T> ContenedorEstado(
    estado: EstadoUi<T>,
    modifier: Modifier = Modifier,
    onReintentar: (() -> Unit)? = null,
    contenido: @Composable (T) -> Unit,
) {
    when (estado) {
        is EstadoUi.Cargando -> CentradoVertical(modifier) {
            CircularProgressIndicator()
            Text("Cargando…", style = MaterialTheme.typography.bodyMedium)
        }

        is EstadoUi.Vacio -> CentradoVertical(modifier) {
            Text(
                estado.mensaje,
                style = MaterialTheme.typography.bodyLarge,
                textAlign = TextAlign.Center,
            )
        }

        is EstadoUi.Error -> CentradoVertical(modifier) {
            Text(
                estado.mensaje,
                color = MaterialTheme.colorScheme.error,
                style = MaterialTheme.typography.bodyLarge,
                textAlign = TextAlign.Center,
            )
            if (onReintentar != null) {
                Button(onClick = onReintentar, modifier = Modifier.objetivoTactil()) {
                    Text("Reintentar")
                }
            }
        }

        is EstadoUi.Contenido -> contenido(estado.datos)
    }
}

@Composable
private fun CentradoVertical(modifier: Modifier, contenido: @Composable () -> Unit) {
    Column(
        modifier = modifier.fillMaxSize().padding(Dimens.EspacioL),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
    ) { contenido() }
}
