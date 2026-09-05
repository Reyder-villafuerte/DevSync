package pe.edu.upeu.milkflow.presentation.auditoria

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.presentation.components.EmptyState
import pe.edu.upeu.milkflow.presentation.components.ErrorState
import pe.edu.upeu.milkflow.presentation.components.LoadingState
import pe.edu.upeu.milkflow.presentation.components.MilkFlowCard
import pe.edu.upeu.milkflow.presentation.components.SectionTitle
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun AuditoriaScreen(
    state: AuditoriaUiState,
    onEvent: (AuditoriaUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        AuditoriaContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando historial de auditoría…",
        )
        AuditoriaContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin registros de auditoría",
            message = "Las acciones auditables aparecerán en esta sección.",
        )
        is AuditoriaContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(AuditoriaUiEvent.Retry) },
        )
        is AuditoriaContentState.Success -> LazyColumn(
            modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Auditoría",
                    supportingText = "Historial de consulta. No permite edición manual.",
                )
            }
            items(content.registros, key = AuditoriaUiItem::id) { item ->
                MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                    Text(item.accion, style = MaterialTheme.typography.titleMedium)
                    Text("Usuario: ${item.usuarioId}", color = MilkFlowColors.TextSecondary)
                    Text("Fecha y hora: ${item.fechaHora}", color = MilkFlowColors.TextSecondary)
                    Text("Registro: ${item.registroAfectadoId}", color = MilkFlowColors.TextSecondary)
                }
            }
        }
    }
}
