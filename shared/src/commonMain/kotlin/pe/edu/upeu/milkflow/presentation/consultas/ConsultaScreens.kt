package pe.edu.upeu.milkflow.presentation.consultas

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
fun ConsultarEntregasProductorScreen(
    state: ConsultaUiState,
    onEvent: (ConsultaUiEvent) -> Unit,
    onVerResumen: () -> Unit,
    modifier: Modifier = Modifier,
) {
    if (state.productores.isEmpty() && state.content == ConsultaContentState.Loading) {
        LoadingState(modifier = modifier.padding(MilkFlowSpacing.Medium), message = "Cargando productores…")
        return
    }
    LazyColumn(
        modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(
                title = "Consultar entregas del productor",
                supportingText = "Consulta únicamente los registros del productor seleccionado.",
            )
        }
        item { SectionTitle(title = "Productor") }
        items(state.productores, key = Productor::id) { productor ->
            SelectorCard(
                text = productor.nombre,
                selected = productor.id == state.productorId,
                onClick = { onEvent(ConsultaUiEvent.SelectProductor(productor.id)) },
            )
        }
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
            ) {
                OutlinedTextField(
                    value = state.fechaInicio,
                    onValueChange = { onEvent(ConsultaUiEvent.FechaInicioChanged(it)) },
                    modifier = Modifier.weight(1f),
                    label = { Text("Desde") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text),
                )
                OutlinedTextField(
                    value = state.fechaFin,
                    onValueChange = { onEvent(ConsultaUiEvent.FechaFinChanged(it)) },
                    modifier = Modifier.weight(1f),
                    label = { Text("Hasta") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text),
                )
            }
        }
        item {
            PrimaryButton(
                text = "Buscar entregas",
                onClick = { onEvent(ConsultaUiEvent.Buscar) },
                enabled = state.productorId != null && state.rangoValido,
            )
        }
        item { ConsultaResult(state.content) }
        item { SecondaryButton(text = "Ver resumen del productor", onClick = onVerResumen) }
    }
}

@Composable
private fun ConsultaResult(content: ConsultaContentState) {
    when (content) {
        ConsultaContentState.Loading -> LoadingState(message = "Consultando entregas…")
        ConsultaContentState.Empty -> EmptyState(
            title = "Sin entregas en el rango",
            message = "No existen registros para el productor y fechas seleccionados.",
        )
        is ConsultaContentState.Error -> ErrorState(content.message)
        is ConsultaContentState.Success -> Column(
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
        ) {
            SectionTitle(title = "Resultados")
            content.entregas.forEach { entrega -> ConsultaEntregaCard(entrega) }
        }
    }
}

@Composable
private fun SelectorCard(text: String, selected: Boolean, onClick: () -> Unit) {
    MilkFlowCard(modifier = Modifier.fillMaxWidth().clickable(onClick = onClick)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(text, style = MaterialTheme.typography.titleMedium)
            if (selected) StatusChip(text = "SELECCIONADO", tone = StatusTone.SUCCESS)
        }
    }
}

@Composable
private fun ConsultaEntregaCard(entrega: ConsultaEntregaUi) {
    MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
        ) {
            Text(entrega.fechaHora, style = MaterialTheme.typography.bodySmall, color = MilkFlowColors.TextSecondary)
            Text(
                "${entrega.litros} L",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MilkFlowColors.Primary,
            )
        }
        Row(
            modifier = Modifier.padding(top = MilkFlowSpacing.Small),
            horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
        ) {
            StatusChip(
                text = if (entrega.tipo == TipoEntrega.DIRECTA) "DIRECTA" else "RECOGIDA",
                tone = if (entrega.tipo == TipoEntrega.DIRECTA) StatusTone.NEUTRAL else StatusTone.WARNING,
            )
            StatusChip(entrega.estadoSincronizacion.nombreVisible(), entrega.estadoSincronizacion.tono())
        }
        if (entrega.tipo == TipoEntrega.RECOGIDA) {
            Text("Acopiador: ${entrega.acopiadorId}", color = MilkFlowColors.TextSecondary)
            Text("Sector: ${entrega.sector}", color = MilkFlowColors.TextSecondary)
        }
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

@Composable
fun ResumenProductorScreen(
    state: ResumenUiState,
    onEvent: (ResumenUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    if (state.productores.isEmpty() && state.content == ResumenContentState.Loading) {
        LoadingState(modifier = modifier.padding(MilkFlowSpacing.Medium), message = "Cargando productores…")
        return
    }
    LazyColumn(
        modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(
                title = "Resumen del productor",
                supportingText = "Consulta semanal o mensual con datos reales de entregas.",
            )
        }
        item { SectionTitle(title = "Productor") }
        items(state.productores, key = Productor::id) { productor ->
            SelectorCard(
                text = productor.nombre,
                selected = productor.id == state.productorId,
                onClick = { onEvent(ResumenUiEvent.SelectProductor(productor.id)) },
            )
        }
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
            ) {
                SecondaryButton(
                    text = if (state.periodo == ResumenPeriodo.SEMANAL) "✓ Semanal" else "Semanal",
                    onClick = { onEvent(ResumenUiEvent.PeriodoChanged(ResumenPeriodo.SEMANAL)) },
                    modifier = Modifier.weight(1f),
                )
                SecondaryButton(
                    text = if (state.periodo == ResumenPeriodo.MENSUAL) "✓ Mensual" else "Mensual",
                    onClick = { onEvent(ResumenUiEvent.PeriodoChanged(ResumenPeriodo.MENSUAL)) },
                    modifier = Modifier.weight(1f),
                )
            }
        }
        item {
            OutlinedTextField(
                value = state.fechaReferencia,
                onValueChange = { onEvent(ResumenUiEvent.FechaChanged(it)) },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Fecha de referencia (AAAA-MM-DD)") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text),
            )
        }
        item {
            PrimaryButton(
                text = "Consultar resumen",
                onClick = { onEvent(ResumenUiEvent.Consultar) },
                enabled = state.productorId != null && state.fechaReferencia.parseDateOrNull() != null,
            )
        }
        item {
            when (val content = state.content) {
                ResumenContentState.Loading -> LoadingState(message = "Calculando resumen…")
                ResumenContentState.Empty -> EmptyState(
                    title = "Sin entregas en el período",
                    message = "No existen litros ni entregas para mostrar.",
                )
                is ResumenContentState.Error -> ErrorState(content.message)
                is ResumenContentState.Success -> MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                    Text("Total de litros", style = MaterialTheme.typography.labelLarge)
                    Text(
                        text = "${content.resumen.totalLitros} L",
                        modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                        style = MaterialTheme.typography.headlineMedium,
                        color = MilkFlowColors.Primary,
                    )
                    Text(
                        text = "${content.resumen.cantidadEntregas} entregas",
                        color = MilkFlowColors.TextSecondary,
                    )
                    Text(
                        text = "Período: ${content.resumen.rango.inicio} – ${content.resumen.rango.finExclusivo}",
                        color = MilkFlowColors.TextSecondary,
                    )
                }
            }
        }
    }
}
