package pe.edu.upeu.milkflow.presentation.components

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun LoadingState(
    modifier: Modifier = Modifier,
    message: String = "Cargando…",
) {
    StateContainer(modifier) {
        CircularProgressIndicator(color = MilkFlowColors.Primary)
        Text(message, style = MaterialTheme.typography.bodyMedium)
    }
}

@Composable
fun EmptyState(
    title: String = "Sin información",
    message: String = "Todavía no hay registros para mostrar.",
    modifier: Modifier = Modifier,
    actionText: String? = null,
    onAction: (() -> Unit)? = null,
) {
    StateContainer(modifier) {
        Text(title, style = MaterialTheme.typography.titleMedium)
        Text(
            text = message,
            style = MaterialTheme.typography.bodyMedium,
            color = MilkFlowColors.TextSecondary,
        )
        if (actionText != null && onAction != null) {
            SecondaryButton(text = actionText, onClick = onAction)
        }
    }
}

@Composable
fun ErrorState(
    message: String,
    modifier: Modifier = Modifier,
    onRetry: (() -> Unit)? = null,
) {
    StateContainer(modifier) {
        Text(
            text = "No se pudo completar la operación",
            style = MaterialTheme.typography.titleMedium,
            color = MilkFlowColors.Error,
        )
        Text(
            text = message,
            style = MaterialTheme.typography.bodyMedium,
            color = MilkFlowColors.TextSecondary,
        )
        if (onRetry != null) {
            SecondaryButton(text = "Reintentar", onClick = onRetry)
        }
    }
}

@Composable
private fun StateContainer(
    modifier: Modifier,
    content: @Composable () -> Unit,
) {
    MilkFlowCard(modifier = modifier.fillMaxWidth()) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(MilkFlowSpacing.Small),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            content()
        }
    }
}
