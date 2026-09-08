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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.TipoProducto
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
fun RegistrarLoteScreen(
    state: ProduccionUiState,
    onEvent: (ProduccionUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    LazyColumn(
        modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium)
    ) {
        item {
            SectionTitle(
                title = "Registrar Lote de Producción",
                supportingText = "Registra los litros de leche utilizados y los moldes de queso obtenidos."
            )
        }

        item {
            MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                Text(text = "Tipo de producto", style = MaterialTheme.typography.labelLarge)
                TipoProducto.entries.forEach { tipo ->
                    SecondaryButton(
                        text = if (state.tipoProducto == tipo) "✓ ${tipo.nombreVisible()}" else tipo.nombreVisible(),
                        onClick = { onEvent(ProduccionUiEvent.TipoProductoChanged(tipo)) },
                        modifier = Modifier.padding(top = MilkFlowSpacing.Small)
                    )
                }
            }
        }

        item {
            OutlinedTextField(
                value = state.litrosLeche,
                onValueChange = { onEvent(ProduccionUiEvent.LitrosLecheChanged(it)) },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Litros de leche utilizados") },
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                singleLine = true
            )
        }

        if (state.tipoProducto.esQueso()) {
            item {
                OutlinedTextField(
                    value = state.moldes,
                    onValueChange = { onEvent(ProduccionUiEvent.MoldesChanged(it)) },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Moldes obtenidos") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    singleLine = true
                )
            }
        }

        item {
            OutlinedTextField(
                value = state.observaciones,
                onValueChange = { onEvent(ProduccionUiEvent.ObservacionesChanged(it)) },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Observaciones (opcional)") },
                minLines = 3
            )
        }

        item {
            SubmissionMessage(state.submission)
            PrimaryButton(
                text = if (state.submission == ProduccionSubmissionState.Saving) "Guardando..." else "Registrar lote",
                onClick = { onEvent(ProduccionUiEvent.RegistrarLote) },
                enabled = state.formularioValido && state.submission != ProduccionSubmissionState.Saving
            )
        }
    }
}

@Composable
fun LotesProduccionScreen(
    state: ProduccionUiState,
    onEvent: (ProduccionUiEvent) -> Unit,
    onRegistrarLote: () -> Unit,
    modifier: Modifier = Modifier,
    soloHoy: Boolean = false,
) {
    when (val content = state.content) {
        ProduccionContentState.Loading -> LoadingState(modifier = modifier.padding(MilkFlowSpacing.Medium))
        is ProduccionContentState.Error -> ErrorState(
            message = content.message,
            onRetry = { onEvent(ProduccionUiEvent.Load) }
        )
        else -> {
            LazyColumn(
                modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
                verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium)
            ) {
                item {
                    SectionTitle(
                        title = if (soloHoy) "Producción de hoy" else "Lotes de producción",
                        supportingText = if (soloHoy) "Lotes procesados durante el día actual." else "Historial completo de lotes registrados."
                    )
                }

                if (state.lotes.isEmpty()) {
                    item {
                        EmptyState(
                            title = if (soloHoy) "Sin lotes hoy" else "Sin lotes registrados",
                            message = "La actividad de producción aparecerá aquí.",
                            actionText = "Registrar lote",
                            onAction = onRegistrarLote
                        )
                    }
                } else {
                    items(state.lotes, key = { it.id }) { lote ->
                        LoteCard(lote)
                    }
                    
                    item {
                        PrimaryButton(text = "Registrar nuevo lote", onClick = onRegistrarLote)
                    }
                }
            }
        }
    }
}

@Composable
private fun LoteCard(lote: LoteProduccion) {
    MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(text = lote.tipoProducto.nombreVisible(), style = MaterialTheme.typography.titleMedium)
                Text(text = lote.fechaHora.toString(), style = MaterialTheme.typography.bodySmall, color = MilkFlowColors.TextSecondary)
            }
            if (lote.tipoProducto.esQueso()) {
                Text(
                    text = "${lote.moldesObtenidos} moldes",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = MilkFlowColors.Primary
                )
            }
        }
        
        Spacer(Modifier.height(MilkFlowSpacing.Small))
        
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium)) {
            Column {
                Text(text = "Leche", style = MaterialTheme.typography.labelSmall, color = MilkFlowColors.TextSecondary)
                Text(text = "${lote.litrosLecheUtilizados} L", style = MaterialTheme.typography.bodyMedium)
            }
            lote.rendimiento?.let { rend ->
                Column {
                    Text(text = "Rendimiento", style = MaterialTheme.typography.labelSmall, color = MilkFlowColors.TextSecondary)
                    Text(
                        text = "${(rend * 10).toInt() / 10.0} moldes / 100 L", 
                        style = MaterialTheme.typography.bodyMedium,
                        color = if (lote.dentroDelRango == true) MilkFlowColors.Success else MilkFlowColors.Warning
                    )
                }
            }
        }
        
        lote.rendimiento?.let {
            StatusChip(
                text = if (lote.dentroDelRango == true) "Dentro del rendimiento esperado" else "Fuera del rendimiento esperado",
                tone = if (lote.dentroDelRango == true) StatusTone.SUCCESS else StatusTone.WARNING,
                modifier = Modifier.padding(top = MilkFlowSpacing.Small)
            )
        }
    }
}

@Composable
private fun SubmissionMessage(submission: ProduccionSubmissionState) {
    val message = when (submission) {
        is ProduccionSubmissionState.Success -> submission.message
        is ProduccionSubmissionState.Error -> submission.message
        else -> null
    }
    if (message != null) {
        Text(
            text = message,
            modifier = Modifier.padding(bottom = MilkFlowSpacing.Medium),
            color = if (submission is ProduccionSubmissionState.Error) MilkFlowColors.Error else MilkFlowColors.Success,
            style = MaterialTheme.typography.bodyMedium
        )
    }
}
