package pe.edu.upeu.milkflow.presentation.inicio

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import pe.edu.upeu.milkflow.presentation.components.EmptyState
import pe.edu.upeu.milkflow.presentation.components.ErrorState
import pe.edu.upeu.milkflow.presentation.components.LoadingState
import pe.edu.upeu.milkflow.presentation.components.MilkFlowCard
import pe.edu.upeu.milkflow.presentation.components.PrimaryButton
import pe.edu.upeu.milkflow.presentation.components.SecondaryButton
import pe.edu.upeu.milkflow.presentation.components.SectionTitle
import pe.edu.upeu.milkflow.presentation.components.StatusChip
import pe.edu.upeu.milkflow.presentation.components.StatusTone
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun InicioScreen(
    state: InicioUiState,
    onEvent: (InicioUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        InicioContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando resumen del día…",
        )
        is InicioContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(InicioUiEvent.Refresh) },
        )
        is InicioContentState.Empty -> DashboardContent(
            data = content.data,
            isEmpty = true,
            onEvent = onEvent,
            modifier = modifier,
        )
        is InicioContentState.Success -> DashboardContent(
            data = content.data,
            isEmpty = false,
            onEvent = onEvent,
            modifier = modifier,
        )
    }
}

@Composable
private fun DashboardContent(
    data: InicioDashboardData,
    isEmpty: Boolean,
    onEvent: (InicioUiEvent) -> Unit,
    modifier: Modifier,
) {
    LazyColumn(
        modifier = modifier
            .fillMaxSize()
            .padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(
                title = "Hola, ${data.nombreUsuario}",
                supportingText = data.rol,
            )
        }
        item {
            MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                Text(
                    text = "Estado de sincronización",
                    style = MaterialTheme.typography.labelLarge,
                )
                StatusChip(
                    text = if (data.registrosPendientes == 0) {
                        "Sin registros pendientes"
                    } else {
                        "${data.registrosPendientes} registros pendientes"
                    },
                    tone = if (data.registrosPendientes == 0) {
                        StatusTone.SUCCESS
                    } else {
                        StatusTone.WARNING
                    },
                    modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                )
            }
        }
        
        item { SectionTitle(title = "Resumen del día") }
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
            ) {
                SummaryCard(
                    title = "Total de litros",
                    value = "${data.totalLitrosHoy} L",
                    modifier = Modifier.weight(1f),
                )
                SummaryCard(
                    title = "Entregas",
                    value = data.cantidadEntregasHoy.toString(),
                    modifier = Modifier.weight(1f),
                )
            }
        }
        
        if (data.mostrarMensajePendiente) {
            item {
                MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                    Text(
                        text = "Operaciones de despacho",
                        style = MaterialTheme.typography.titleMedium,
                        color = MilkFlowColors.Primary
                    )
                    Spacer(Modifier.height(MilkFlowSpacing.Small))
                    Text(
                        text = "Funciones de despacho pendientes de definición técnica.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MilkFlowColors.TextSecondary
                    )
                }
            }
        }

        if (data.acciones.isNotEmpty()) {
            item { SectionTitle(title = "Accesos rápidos") }
            item {
                Column(verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small)) {
                    val principal = data.acciones.firstOrNull { it.principal }
                    val secundarias = data.acciones.filter { it != principal }
                    
                    principal?.let { accion ->
                        PrimaryButton(
                            text = accion.titulo,
                            onClick = { onEvent(InicioUiEvent.Navigate(accion.navigation)) }
                        )
                    }
                    
                    secundarias.chunked(2).forEach { par ->
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small)
                        ) {
                            par.forEach { accion ->
                                SecondaryButton(
                                    text = accion.titulo,
                                    onClick = { onEvent(InicioUiEvent.Navigate(accion.navigation)) },
                                    modifier = Modifier.weight(1f)
                                )
                            }
                            if (par.size == 1) {
                                Spacer(Modifier.weight(1f))
                            }
                        }
                    }
                }
            }
        }

        if (!data.mostrarMensajePendiente) {
            item { SectionTitle(title = "Registros recientes") }
            if (isEmpty || data.entregasRecientes.isEmpty()) {
                item {
                    EmptyState(
                        title = "Sin entregas registradas hoy",
                        message = "Los registros del día aparecerán en esta sección.",
                    )
                }
            } else {
                items(data.entregasRecientes, key = { it.id }) { entrega ->
                    MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = entrega.tipo,
                                    style = MaterialTheme.typography.titleMedium,
                                    color = MilkFlowColors.TextPrimary,
                                )
                                Text(
                                    text = entrega.fechaHora,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MilkFlowColors.TextSecondary,
                                )
                            }
                            Text(
                                text = "${entrega.litros} L",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = MilkFlowColors.Primary,
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SummaryCard(
    title: String,
    value: String,
    modifier: Modifier,
) {
    MilkFlowCard(modifier = modifier) {
        Text(title, style = MaterialTheme.typography.bodyMedium, color = MilkFlowColors.TextSecondary)
        Text(value, style = MaterialTheme.typography.headlineSmall, color = MilkFlowColors.Primary)
    }
}
