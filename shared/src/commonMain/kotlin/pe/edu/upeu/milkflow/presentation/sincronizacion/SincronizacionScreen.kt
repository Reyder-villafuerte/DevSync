package pe.edu.upeu.milkflow.presentation.sincronizacion

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.presentation.components.EmptyState
import pe.edu.upeu.milkflow.presentation.components.ErrorState
import pe.edu.upeu.milkflow.presentation.components.LoadingState
import pe.edu.upeu.milkflow.presentation.components.MilkFlowCard
import pe.edu.upeu.milkflow.presentation.components.PrimaryButton
import pe.edu.upeu.milkflow.presentation.components.SectionTitle
import pe.edu.upeu.milkflow.presentation.components.StatusChip
import pe.edu.upeu.milkflow.presentation.components.StatusTone
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun SincronizacionScreen(
    state: SincronizacionUiState,
    onEvent: (SincronizacionUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        SincronizacionContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando estado de sincronización…",
        )
        SincronizacionContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin registros locales",
            message = "Todavía no hay registros para sincronizar.",
        )
        is SincronizacionContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(SincronizacionUiEvent.Retry) },
        )
        is SincronizacionContentState.Success -> LazyColumn(
            modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Sincronización",
                    supportingText = "Estado local de registros PENDIENTE, ENVIADO y ERROR.",
                )
            }
            item {
                MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                    Text(
                        text = "Pendientes: ${state.pendientes}",
                        style = MaterialTheme.typography.titleMedium,
                    )
                    Text(
                        text = if (state.backendDisponible) {
                            "Backend remoto disponible y operativo."
                        } else {
                            "Sin backend remoto configurado: la app conserva flujo local y registra ERROR al intentar enviar."
                        },
                        modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                        color = MilkFlowColors.TextSecondary,
                    )
                    val message = when (val submission = state.submission) {
                        SincronizacionSubmissionState.Idle,
                        SincronizacionSubmissionState.Syncing -> null
                        is SincronizacionSubmissionState.Success -> submission.message
                        is SincronizacionSubmissionState.Error -> submission.message
                    }
                    if (message != null) {
                        Text(
                            text = message,
                            modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                            color = if (state.submission is SincronizacionSubmissionState.Error) {
                                MilkFlowColors.Error
                            } else {
                                MilkFlowColors.Success
                            },
                        )
                    }
                    PrimaryButton(
                        text = if (state.submission == SincronizacionSubmissionState.Syncing) {
                            "Sincronizando…"
                        } else {
                            "Sincronizar pendientes"
                        },
                        enabled = state.puedeSincronizar &&
                            state.submission != SincronizacionSubmissionState.Syncing,
                        onClick = { onEvent(SincronizacionUiEvent.SincronizarPendientes) },
                        modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
                    )
                }
            }
            items(content.registros, key = { "${it.tipoRegistro}-${it.registroId}" }) { registro ->
                MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(registro.tipoRegistro, style = MaterialTheme.typography.labelLarge)
                            Text(
                                text = "Registro: ${registro.registroId}",
                                color = MilkFlowColors.TextSecondary,
                            )
                        }
                        StatusChip(
                            text = registro.estado.name,
                            tone = registro.estado.toStatusTone(),
                        )
                    }
                }
            }
        }
    }
}

private fun EstadoSincronizacion.toStatusTone(): StatusTone = when (this) {
    EstadoSincronizacion.PENDIENTE -> StatusTone.WARNING
    EstadoSincronizacion.ENVIADO -> StatusTone.SUCCESS
    EstadoSincronizacion.ERROR -> StatusTone.ERROR
}
