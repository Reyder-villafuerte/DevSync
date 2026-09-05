package pe.edu.upeu.milkflow.presentation.acopiador

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.presentation.components.EmptyState
import pe.edu.upeu.milkflow.presentation.components.ErrorState
import pe.edu.upeu.milkflow.presentation.components.LoadingState
import pe.edu.upeu.milkflow.presentation.components.MilkFlowCard
import pe.edu.upeu.milkflow.presentation.components.PrimaryButton
import pe.edu.upeu.milkflow.presentation.components.SectionTitle
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun AcopiadoresScreen(
    state: AcopiadorUiState,
    onEvent: (AcopiadorUiEvent) -> Unit,
    onRegistrarAcopiador: () -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        AcopiadorContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando acopiadores…",
        )
        AcopiadorContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin acopiadores",
            message = "Todavía no existen acopiadores registrados.",
            actionText = "Registrar acopiador",
            onAction = onRegistrarAcopiador,
        )
        is AcopiadorContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(AcopiadorUiEvent.Retry) },
        )
        is AcopiadorContentState.Success -> LazyColumn(
            modifier = modifier
                .fillMaxSize()
                .padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Acopiadores",
                    supportingText = "Responsables de recoger leche durante las rutas.",
                )
            }
            items(content.acopiadores, key = Acopiador::id) { acopiador ->
                MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                    Text(
                        text = acopiador.nombre,
                        style = MaterialTheme.typography.titleMedium,
                        color = MilkFlowColors.TextPrimary,
                    )
                    Text(
                        text = "Acopiador registrado",
                        style = MaterialTheme.typography.bodySmall,
                        color = MilkFlowColors.TextSecondary,
                    )
                }
            }
            item {
                PrimaryButton(
                    text = "Registrar acopiador",
                    onClick = onRegistrarAcopiador,
                )
            }
        }
    }
}

@Composable
fun RegistrarAcopiadorScreen(
    state: AcopiadorUiState,
    onEvent: (AcopiadorUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    LazyColumn(
        modifier = modifier
            .fillMaxSize()
            .padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(
                title = "Registrar acopiador",
                supportingText = "Ingresa los datos definidos para el responsable de recojo.",
            )
        }
        item {
            MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                OutlinedTextField(
                    value = state.nombre,
                    onValueChange = { onEvent(AcopiadorUiEvent.NombreChanged(it)) },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Nombre") },
                    singleLine = true,
                    enabled = state.registration != AcopiadorRegistrationState.Saving,
                )
                RegistrationMessage(state.registration)
                PrimaryButton(
                    text = if (state.registration == AcopiadorRegistrationState.Saving) {
                        "Guardando…"
                    } else {
                        "Registrar acopiador"
                    },
                    onClick = { onEvent(AcopiadorUiEvent.Register) },
                    enabled = state.formularioValido &&
                        state.registration != AcopiadorRegistrationState.Saving,
                    modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
                )
            }
        }
    }
}

@Composable
private fun RegistrationMessage(registration: AcopiadorRegistrationState) {
    val message = when (registration) {
        AcopiadorRegistrationState.Idle, AcopiadorRegistrationState.Saving -> null
        is AcopiadorRegistrationState.Success -> registration.message
        is AcopiadorRegistrationState.Error -> registration.message
    }
    if (message != null) {
        Text(
            text = message,
            modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
            color = if (registration is AcopiadorRegistrationState.Error) {
                MilkFlowColors.Error
            } else {
                MilkFlowColors.Success
            },
            style = MaterialTheme.typography.bodyMedium,
        )
    }
}
