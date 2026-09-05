package pe.edu.upeu.milkflow.presentation.reportes

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.foundation.text.KeyboardOptions
import pe.edu.upeu.milkflow.presentation.components.EmptyState
import pe.edu.upeu.milkflow.presentation.components.ErrorState
import pe.edu.upeu.milkflow.presentation.components.LoadingState
import pe.edu.upeu.milkflow.presentation.components.MilkFlowCard
import pe.edu.upeu.milkflow.presentation.components.PrimaryButton
import pe.edu.upeu.milkflow.presentation.components.SecondaryButton
import pe.edu.upeu.milkflow.presentation.components.SectionTitle
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun ReportesScreen(
    state: ReporteUiState,
    onEvent: (ReporteUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    LazyColumn(
        modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(
                title = "Reportes",
                supportingText = "Consulta litros y entregas por día, semana o mes.",
            )
        }
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small),
            ) {
                SecondaryButton(
                    text = if (state.periodo == ReportePeriodo.DIARIO) "✓ Día" else "Día",
                    onClick = { onEvent(ReporteUiEvent.PeriodoChanged(ReportePeriodo.DIARIO)) },
                    modifier = Modifier.weight(1f),
                )
                SecondaryButton(
                    text = if (state.periodo == ReportePeriodo.SEMANAL) "✓ Semana" else "Semana",
                    onClick = { onEvent(ReporteUiEvent.PeriodoChanged(ReportePeriodo.SEMANAL)) },
                    modifier = Modifier.weight(1f),
                )
                SecondaryButton(
                    text = if (state.periodo == ReportePeriodo.MENSUAL) "✓ Mes" else "Mes",
                    onClick = { onEvent(ReporteUiEvent.PeriodoChanged(ReportePeriodo.MENSUAL)) },
                    modifier = Modifier.weight(1f),
                )
            }
        }
        item {
            OutlinedTextField(
                value = state.fechaReferencia,
                onValueChange = { onEvent(ReporteUiEvent.FechaChanged(it)) },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Fecha de referencia (AAAA-MM-DD)") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text),
            )
        }
        item {
            PrimaryButton(
                text = "Generar reporte",
                onClick = { onEvent(ReporteUiEvent.Consultar) },
                enabled = state.formularioValido,
            )
        }
        item { ReporteResult(state.content) }
    }
}

@Composable
private fun ReporteResult(content: ReporteContentState) {
    when (content) {
        ReporteContentState.Loading -> LoadingState(message = "Generando reporte…")
        ReporteContentState.Empty -> EmptyState(
            title = "Sin datos en el período",
            message = "No existen entregas para las fechas seleccionadas.",
        )
        is ReporteContentState.Error -> ErrorState(content.message)
        is ReporteContentState.Success -> MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
            Text("Total de litros", style = MaterialTheme.typography.labelLarge)
            Text(
                text = "${content.reporte.totalLitros} L",
                modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MilkFlowColors.Primary,
            )
            Text(
                text = "${content.reporte.cantidadEntregas} entregas",
                color = MilkFlowColors.TextSecondary,
            )
            Text(
                text = "Período: ${content.reporte.rango.inicio} – ${content.reporte.rango.finExclusivo}",
                color = MilkFlowColors.TextSecondary,
            )
        }
    }
}
