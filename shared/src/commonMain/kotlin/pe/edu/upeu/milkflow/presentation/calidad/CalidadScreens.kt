package pe.edu.upeu.milkflow.presentation.calidad

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
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
fun CalidadScreen(
    state: CalidadUiState,
    onEvent: (CalidadUiEvent) -> Unit,
    onRegistrarPrueba: () -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        CalidadContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando entregas para revisión…",
        )
        CalidadContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin entregas para revisar",
            message = "Registra una entrega antes de realizar el control de calidad.",
        )
        is CalidadContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(CalidadUiEvent.Retry) },
        )
        is CalidadContentState.Success -> LazyColumn(
            modifier = modifier
                .fillMaxSize()
                .padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Control de calidad",
                    supportingText = "Selecciona una entrega para revisar su información.",
                )
            }
            items(content.entregas, key = CalidadEntregaUi::id) { entrega ->
                CalidadEntregaCard(
                    entrega = entrega,
                    selected = entrega.id == state.entregaSeleccionadaId,
                    onClick = { onEvent(CalidadUiEvent.SelectEntrega(entrega.id)) },
                )
            }
            item { CalidadDetail(state = state) }
            item {
                PrimaryButton(
                    text = "Registrar prueba de calidad",
                    onClick = onRegistrarPrueba,
                    enabled = state.entregaSeleccionadaId != null,
                )
            }
        }
    }
}

@Composable
fun RegistrarPruebaCalidadScreen(
    state: CalidadUiState,
    onEvent: (CalidadUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        CalidadContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Preparando control de calidad…",
        )
        CalidadContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin entregas disponibles",
            message = "No hay entregas para registrar una prueba u observación.",
        )
        is CalidadContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(CalidadUiEvent.Retry) },
        )
        is CalidadContentState.Success -> LazyColumn(
            modifier = modifier
                .fillMaxSize()
                .padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Registrar prueba de calidad",
                    supportingText = "La prueba se vincula a la entrega seleccionada. No se registran mediciones no definidas.",
                )
            }
            item { SectionTitle(title = "Entrega") }
            items(content.entregas, key = CalidadEntregaUi::id) { entrega ->
                CalidadEntregaCard(
                    entrega = entrega,
                    selected = entrega.id == state.entregaSeleccionadaId,
                    onClick = { onEvent(CalidadUiEvent.SelectEntrega(entrega.id)) },
                )
            }
            item {
                MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                    val selected = state.entregaSeleccionada
                    Text(
                        text = selected?.let { "Entrega seleccionada: ${it.id}" }
                            ?: "Selecciona una entrega para continuar.",
                        style = MaterialTheme.typography.titleMedium,
                    )
                    val detail = state.detalle
                    if (detail is CalidadDetalleState.Loaded && detail.prueba == null) {
                        PrimaryButton(
                            text = "Registrar prueba",
                            onClick = { onEvent(CalidadUiEvent.RegistrarPrueba) },
                            enabled = state.submission != CalidadSubmissionState.Saving,
                            modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
                        )
                    } else if (detail is CalidadDetalleState.Loaded) {
                        StatusChip(
                            text = "Prueba registrada",
                            tone = StatusTone.SUCCESS,
                            modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
                        )
                    }
                    OutlinedTextField(
                        value = state.observacion,
                        onValueChange = { onEvent(CalidadUiEvent.ObservacionChanged(it)) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = MilkFlowSpacing.Medium),
                        label = { Text("Observación o problema") },
                        enabled = state.entregaSeleccionadaId != null &&
                            state.submission != CalidadSubmissionState.Saving,
                    )
                    SecondaryButton(
                        text = "Registrar observación",
                        onClick = { onEvent(CalidadUiEvent.RegistrarProblema) },
                        enabled = state.entregaSeleccionadaId != null &&
                            state.observacion.isNotBlank() &&
                            state.submission != CalidadSubmissionState.Saving,
                        modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
                    )
                    SubmissionMessage(state.submission)
                }
            }
            item { CalidadDetail(state = state) }
        }
    }
}

@Composable
private fun CalidadEntregaCard(
    entrega: CalidadEntregaUi,
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
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = "Entrega ${entrega.id}",
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
        Row(
            modifier = Modifier.padding(top = MilkFlowSpacing.Small),
            horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            StatusChip(
                text = if (entrega.tipo == TipoEntrega.DIRECTA) "DIRECTA" else "RECOGIDA",
                tone = if (entrega.tipo == TipoEntrega.DIRECTA) StatusTone.NEUTRAL else StatusTone.WARNING,
            )
            if (selected) StatusChip(text = "SELECCIONADA", tone = StatusTone.SUCCESS)
        }
        StatusChip(
            text = entrega.estadoSincronizacion.nombreVisible(),
            tone = entrega.estadoSincronizacion.tono(),
            modifier = Modifier.padding(top = MilkFlowSpacing.Small),
        )
    }
}

@Composable
private fun CalidadDetail(state: CalidadUiState) {
    when (val detail = state.detalle) {
        CalidadDetalleState.None -> Unit
        CalidadDetalleState.Loading -> LoadingState(message = "Cargando información de calidad…")
        is CalidadDetalleState.Error -> ErrorState(detail.message)
        is CalidadDetalleState.Loaded -> MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
            Text("Información de calidad", style = MaterialTheme.typography.titleMedium)
            Text(
                text = if (detail.prueba == null) "Sin prueba registrada"
                else "Prueba registrada: ${detail.prueba.id}",
                modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                color = MilkFlowColors.TextSecondary,
            )
            if (detail.problemas.isEmpty()) {
                Text(
                    text = "Sin observaciones registradas",
                    modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                    color = MilkFlowColors.TextSecondary,
                )
            } else {
                detail.problemas.forEach { problema ->
                    Text(
                        text = "• ${problema.descripcion}",
                        modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                        color = MilkFlowColors.Error,
                    )
                }
            }
        }
    }
}

@Composable
private fun SubmissionMessage(submission: CalidadSubmissionState) {
    val message = when (submission) {
        CalidadSubmissionState.Idle, CalidadSubmissionState.Saving -> null
        is CalidadSubmissionState.Success -> submission.message
        is CalidadSubmissionState.Error -> submission.message
    }
    if (message != null) {
        Text(
            text = message,
            modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
            color = if (submission is CalidadSubmissionState.Error) MilkFlowColors.Error
            else MilkFlowColors.Success,
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
