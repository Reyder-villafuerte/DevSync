package pe.edu.upeu.milkflow.presentation.perfil

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.presentation.components.MilkFlowCard
import pe.edu.upeu.milkflow.presentation.components.PrimaryButton
import pe.edu.upeu.milkflow.presentation.components.SectionTitle
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun PerfilScreen(
    state: PerfilUiState,
    onEvent: (PerfilUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    LazyColumn(
        modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(
                title = "Perfil",
                supportingText = "Información real del usuario autenticado.",
            )
        }
        item {
            MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                Text(
                    text = if (state.sesionActiva) state.nombre else "Sin sesión activa",
                    style = MaterialTheme.typography.titleMedium,
                )
                if (state.sesionActiva) {
                    Text("@${state.nombreUsuario}", color = MilkFlowColors.TextSecondary)
                    Text("Rol: ${state.rol?.name}", color = MilkFlowColors.TextSecondary)
                }
            }
        }
        if (state.sesionActiva) {
            item {
                PrimaryButton(
                    text = "Cerrar sesión",
                    onClick = { onEvent(PerfilUiEvent.CerrarSesion) },
                )
            }
        }
    }
}
