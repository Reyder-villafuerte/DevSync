package pe.edu.upeu.milkflow.presentation.productor

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
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.domain.model.Productor
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
fun ProductoresScreen(
    state: ProductorUiState,
    onEvent: (ProductorUiEvent) -> Unit,
    onActualizarProductor: () -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        ProductorContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando productores…",
        )
        ProductorContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin productores registrados",
            message = "No existen productores activos en el sistema.",
            actionText = if (state.puedeRegistrar) "Registrar productor" else null,
            onAction = {
                onEvent(ProductorUiEvent.Nuevo)
                onActualizarProductor()
            },
        )
        is ProductorContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(ProductorUiEvent.Retry) },
        )
        is ProductorContentState.Success -> LazyColumn(
            modifier = modifier
                .fillMaxSize()
                .padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Productores",
                    supportingText = "Consulta productores y verifica su estado.",
                )
            }
            items(content.productores, key = Productor::id) { productor ->
                ProductorCard(
                    productor = productor,
                    selected = state.productorSeleccionadoId == productor.id,
                    onClick = { onEvent(ProductorUiEvent.Select(productor.id)) },
                )
            }
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small)
                ) {
                    if (state.puedeRegistrar) {
                        PrimaryButton(
                            text = "Nuevo productor",
                            onClick = {
                                onEvent(ProductorUiEvent.Nuevo)
                                onActualizarProductor()
                            },
                            modifier = Modifier.weight(1f)
                        )
                    }
                    SecondaryButton(
                        text = "Actualizar datos",
                        onClick = onActualizarProductor,
                        modifier = Modifier.weight(1f)
                    )
                }
            }
        }
    }
}

@Composable
fun ActualizarProductorScreen(
    state: ProductorUiState,
    onEvent: (ProductorUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    val esNuevo = state.productorSeleccionadoId == null

    LazyColumn(
        modifier = modifier
            .fillMaxSize()
            .padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(
                title = if (esNuevo) "Registrar productor" else "Actualizar productor",
                supportingText = if (esNuevo) "Ingresa el nombre del nuevo productor." 
                               else "Selecciona un productor y modifica sus datos existentes.",
            )
        }
        
        if (!esNuevo) {
            when (val content = state.content) {
                ProductorContentState.Loading -> item {
                    LoadingState(message = "Cargando productores…")
                }
                ProductorContentState.Empty -> item {
                    EmptyState(
                        title = "Sin productores",
                        message = "No hay productores disponibles para actualizar.",
                    )
                }
                is ProductorContentState.Error -> item {
                    ErrorState(
                        message = content.message,
                        onRetry = { onEvent(ProductorUiEvent.Retry) },
                    )
                }
                is ProductorContentState.Success -> {
                    items(content.productores, key = Productor::id) { productor ->
                        ProductorCard(
                            productor = productor,
                            selected = state.productorSeleccionadoId == productor.id,
                            onClick = { onEvent(ProductorUiEvent.Select(productor.id)) },
                        )
                    }
                }
            }
        }
        
        item {
            MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                OutlinedTextField(
                    value = state.nombreEditado,
                    onValueChange = { onEvent(ProductorUiEvent.NombreChanged(it)) },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Nombre") },
                    singleLine = true,
                    enabled = state.saveState != ProductorSaveState.Saving,
                )
                if (!esNuevo) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = MilkFlowSpacing.Medium),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Column {
                            Text("Estado", style = MaterialTheme.typography.labelLarge)
                            Text(
                                if (state.activoEditado) "Activo" else "Inactivo",
                                color = MilkFlowColors.TextSecondary,
                            )
                        }
                        Switch(
                            checked = state.activoEditado,
                            onCheckedChange = {
                                onEvent(ProductorUiEvent.ActivoChanged(it))
                            },
                            enabled = state.saveState != ProductorSaveState.Saving,
                        )
                    }
                }
                SaveMessage(state.saveState)
                PrimaryButton(
                    text = if (state.saveState == ProductorSaveState.Saving) {
                        "Guardando…"
                    } else if (esNuevo) {
                        "Registrar productor"
                    } else {
                        "Guardar actualización"
                    },
                    onClick = { 
                        if (esNuevo) onEvent(ProductorUiEvent.Registrar)
                        else onEvent(ProductorUiEvent.Save) 
                    },
                    enabled = state.formularioValido &&
                        state.saveState != ProductorSaveState.Saving,
                    modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
                )
            }
        }
    }
}

@Composable
private fun ProductorCard(
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
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = productor.nombre,
                    style = MaterialTheme.typography.titleMedium,
                    color = MilkFlowColors.TextPrimary,
                )
                Text(
                    text = if (selected) "Seleccionado" else "Toca para consultar",
                    style = MaterialTheme.typography.bodySmall,
                    color = MilkFlowColors.TextSecondary,
                )
            }
            StatusChip(
                text = if (productor.activo) "ACTIVO" else "INACTIVO",
                tone = if (productor.activo) StatusTone.SUCCESS else StatusTone.ERROR,
            )
        }
    }
}

@Composable
private fun SaveMessage(saveState: ProductorSaveState) {
    val message = when (saveState) {
        ProductorSaveState.Idle, ProductorSaveState.Saving -> null
        is ProductorSaveState.Success -> saveState.message
        is ProductorSaveState.Error -> saveState.message
    }
    if (message != null) {
        Text(
            text = message,
            modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
            color = if (saveState is ProductorSaveState.Error) {
                MilkFlowColors.Error
            } else {
                MilkFlowColors.Success
            },
            style = MaterialTheme.typography.bodyMedium,
        )
    }
}
