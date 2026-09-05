package pe.edu.upeu.milkflow.presentation.entrega

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
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
fun EntregasScreen(
    state: EntregaUiState,
    onEvent: (EntregaUiEvent) -> Unit,
    onRegistrarEntrega: () -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        EntregaContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando entregas…",
        )
        EntregaContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin entregas",
            message = "Todavía no existen entregas registradas.",
            actionText = "Registrar entrega",
            onAction = onRegistrarEntrega,
        )
        is EntregaContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(EntregaUiEvent.Retry) },
        )
        is EntregaContentState.Success -> LazyColumn(
            modifier = modifier
                .fillMaxSize()
                .padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Entregas",
                    supportingText = "Recepciones directas y leche recogida.",
                )
            }
            items(content.entregas, key = EntregaUiItem::id) { entrega ->
                EntregaCard(entrega)
            }
            item {
                PrimaryButton(text = "Registrar entrega", onClick = onRegistrarEntrega)
            }
        }
    }
}

@Composable
fun RegistrarEntregaScreen(
    state: EntregaUiState,
    onEvent: (EntregaUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        EntregaContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Preparando formulario…",
        )
        is EntregaContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(EntregaUiEvent.Retry) },
        )
        else -> EntregaForm(state = state, onEvent = onEvent, modifier = modifier)
    }
}

@Composable
private fun EntregaForm(
    state: EntregaUiState,
    onEvent: (EntregaUiEvent) -> Unit,
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
                title = "Registrar entrega",
                supportingText = "La fecha, hora y usuario se registrarán automáticamente.",
            )
        }
        item {
            SectionTitle(title = "Tipo de entrega")
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = MilkFlowSpacing.Small),
                horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
            ) {
                SecondaryButton(
                    text = if (state.tipo == TipoEntrega.DIRECTA) "✓ Directa" else "Directa",
                    onClick = { onEvent(EntregaUiEvent.TipoChanged(TipoEntrega.DIRECTA)) },
                    modifier = Modifier.weight(1f),
                )
                SecondaryButton(
                    text = if (state.tipo == TipoEntrega.RECOGIDA) "✓ Recogida" else "Recogida",
                    onClick = { onEvent(EntregaUiEvent.TipoChanged(TipoEntrega.RECOGIDA)) },
                    modifier = Modifier.weight(1f),
                )
            }
        }
        item { SectionTitle(title = "Productor") }
        if (state.productores.isEmpty()) {
            item {
                EmptyState(
                    title = "Sin productores disponibles",
                    message = "Debe existir un productor activo para registrar una entrega.",
                )
            }
        } else {
            items(state.productores, key = Productor::id) { productor ->
                SelectableProductor(
                    productor = productor,
                    selected = state.productorId == productor.id,
                    onClick = { onEvent(EntregaUiEvent.SelectProductor(productor.id)) },
                )
            }
        }
        item {
            OutlinedTextField(
                value = state.litros,
                onValueChange = { onEvent(EntregaUiEvent.LitrosChanged(it)) },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Litros") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                enabled = state.submission != EntregaSubmissionState.Saving,
            )
        }
        if (state.tipo == TipoEntrega.RECOGIDA) {
            item { SectionTitle(title = "Acopiador") }
            if (state.acopiadores.isEmpty()) {
                item {
                    EmptyState(
                        title = "Sin acopiadores disponibles",
                        message = "Registra un acopiador antes de guardar leche recogida.",
                    )
                }
            } else {
                items(state.acopiadores, key = Acopiador::id) { acopiador ->
                    MilkFlowCard(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable {
                                onEvent(EntregaUiEvent.SelectAcopiador(acopiador.id))
                            },
                    ) {
                        Text(
                            text = acopiador.nombre,
                            style = MaterialTheme.typography.titleMedium,
                            color = MilkFlowColors.TextPrimary,
                        )
                        if (state.acopiadorId == acopiador.id) {
                            Text(
                                text = "Seleccionado",
                                color = MilkFlowColors.Success,
                                style = MaterialTheme.typography.bodySmall,
                            )
                        }
                    }
                }
            }
            item {
                OutlinedTextField(
                    value = state.sector,
                    onValueChange = { onEvent(EntregaUiEvent.SectorChanged(it)) },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Sector") },
                    singleLine = true,
                    enabled = state.submission != EntregaSubmissionState.Saving,
                )
            }
        }
        item {
            SubmissionMessage(state.submission)
            PrimaryButton(
                text = if (state.submission == EntregaSubmissionState.Saving) {
                    "Guardando…"
                } else {
                    "Guardar entrega"
                },
                onClick = { onEvent(EntregaUiEvent.Save) },
                enabled = state.formularioValido && state.submission != EntregaSubmissionState.Saving,
            )
        }
    }
}

@Composable
private fun SelectableProductor(
    productor: Productor,
    selected: Boolean,
    onClick: () -> Unit,
) {
    MilkFlowCard(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(
                text = productor.nombre,
                style = MaterialTheme.typography.titleMedium,
                color = MilkFlowColors.TextPrimary,
                modifier = Modifier.weight(1f),
            )
            StatusChip(
                text = when {
                    !productor.activo -> "INACTIVO"
                    selected -> "SELECCIONADO"
                    else -> "ACTIVO"
                },
                tone = if (productor.activo) StatusTone.SUCCESS else StatusTone.ERROR,
            )
        }
    }
}

@Composable
private fun EntregaCard(entrega: EntregaUiItem) {
    MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            StatusChip(
                text = if (entrega.tipo == TipoEntrega.DIRECTA) "DIRECTA" else "RECOGIDA",
                tone = if (entrega.tipo == TipoEntrega.DIRECTA) {
                    StatusTone.NEUTRAL
                } else {
                    StatusTone.WARNING
                },
            )
            Text(
                text = "${entrega.litros} L",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                color = MilkFlowColors.Primary,
            )
        }
        Text(
            text = entrega.productorNombre,
            modifier = Modifier.padding(top = MilkFlowSpacing.Small),
            style = MaterialTheme.typography.titleMedium,
            color = MilkFlowColors.TextPrimary,
        )
        Text(
            text = entrega.fechaHora,
            style = MaterialTheme.typography.bodySmall,
            color = MilkFlowColors.TextSecondary,
        )
        if (entrega.tipo == TipoEntrega.RECOGIDA) {
            Text(
                text = "Acopiador: ${entrega.acopiadorNombre}",
                style = MaterialTheme.typography.bodyMedium,
                color = MilkFlowColors.TextSecondary,
            )
            Text(
                text = "Sector: ${entrega.sector}",
                style = MaterialTheme.typography.bodyMedium,
                color = MilkFlowColors.TextSecondary,
            )
        }
        StatusChip(
            text = entrega.estadoSincronizacion.nombreVisible(),
            tone = entrega.estadoSincronizacion.tono(),
            modifier = Modifier.padding(top = MilkFlowSpacing.Small),
        )
    }
}

@Composable
private fun SubmissionMessage(submission: EntregaSubmissionState) {
    val message = when (submission) {
        EntregaSubmissionState.Idle, EntregaSubmissionState.Saving -> null
        is EntregaSubmissionState.Success -> submission.message
        is EntregaSubmissionState.Error -> submission.message
    }
    if (message != null) {
        Text(
            text = message,
            modifier = Modifier.padding(bottom = MilkFlowSpacing.Medium),
            color = if (submission is EntregaSubmissionState.Error) {
                MilkFlowColors.Error
            } else {
                MilkFlowColors.Success
            },
            style = MaterialTheme.typography.bodyMedium,
        )
    }
}

private fun EstadoSincronizacion.nombreVisible(): String = when (this) {
    EstadoSincronizacion.PENDIENTE -> "PENDIENTE"
    EstadoSincronizacion.ENVIADO -> "ENVIADO"
    EstadoSincronizacion.ERROR -> "ERROR"
}

private fun EstadoSincronizacion.tono(): StatusTone = when (this) {
    EstadoSincronizacion.PENDIENTE -> StatusTone.WARNING
    EstadoSincronizacion.ENVIADO -> StatusTone.SUCCESS
    EstadoSincronizacion.ERROR -> StatusTone.ERROR
}
