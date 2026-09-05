package pe.edu.upeu.milkflow.presentation.usuarios

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
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
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
fun UsuariosScreen(
    state: UsuariosUiState,
    onEvent: (UsuariosUiEvent) -> Unit,
    modifier: Modifier = Modifier,
) {
    when (val content = state.content) {
        UsuariosContentState.Loading -> LoadingState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            message = "Cargando usuarios…",
        )
        UsuariosContentState.Empty -> EmptyState(
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            title = "Sin usuarios registrados",
            message = "Registra un usuario para habilitar operaciones por rol.",
        )
        is UsuariosContentState.Error -> ErrorState(
            message = content.message,
            modifier = modifier.padding(MilkFlowSpacing.Medium),
            onRetry = { onEvent(UsuariosUiEvent.Retry) },
        )
        is UsuariosContentState.Success -> LazyColumn(
            modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Medium),
            verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
        ) {
            item {
                SectionTitle(
                    title = "Usuarios y permisos",
                    supportingText = "Solo el rol ADMINISTRADORA puede gestionar usuarios.",
                )
            }
            if (state.puedeGestionar) {
                item {
                    SecondaryButton(
                        text = "Nuevo usuario",
                        onClick = { onEvent(UsuariosUiEvent.NuevoUsuario) },
                    )
                }
            }
            items(content.usuarios, key = Usuario::id) { usuario ->
                UsuarioCard(
                    usuario = usuario,
                    selected = state.usuarioSeleccionadoId == usuario.id,
                    onClick = { onEvent(UsuariosUiEvent.SelectUsuario(usuario.id)) },
                )
            }
            if (state.puedeGestionar) {
                item { UsuarioForm(state = state, onEvent = onEvent) }
            }
        }
    }
}

@Composable
private fun UsuarioCard(
    usuario: Usuario,
    selected: Boolean,
    onClick: () -> Unit,
) {
    MilkFlowCard(modifier = Modifier.fillMaxWidth().clickable(onClick = onClick)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(usuario.nombre, style = MaterialTheme.typography.titleMedium)
                Text("@${usuario.nombreUsuario}", color = MilkFlowColors.TextSecondary)
                Text(usuario.rol.nombreVisible(), color = MilkFlowColors.TextSecondary)
            }
            Column(verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Small)) {
                StatusChip(
                    text = if (usuario.activo) "ACTIVO" else "INACTIVO",
                    tone = if (usuario.activo) StatusTone.SUCCESS else StatusTone.ERROR,
                )
                if (selected) StatusChip("SELECCIONADO", StatusTone.NEUTRAL)
            }
        }
    }
}

@Composable
private fun UsuarioForm(
    state: UsuariosUiState,
    onEvent: (UsuariosUiEvent) -> Unit,
) {
    MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
        SectionTitle(
            title = if (state.usuarioSeleccionadoId == null) "Registrar usuario" else "Editar usuario",
        )
        OutlinedTextField(
            value = state.nombreUsuario,
            onValueChange = { onEvent(UsuariosUiEvent.NombreUsuarioChanged(it)) },
            modifier = Modifier.fillMaxWidth().padding(top = MilkFlowSpacing.Small),
            label = { Text("Usuario") },
            singleLine = true,
            enabled = state.saveState != UsuariosSaveState.Saving,
        )
        OutlinedTextField(
            value = state.nombre,
            onValueChange = { onEvent(UsuariosUiEvent.NombreChanged(it)) },
            modifier = Modifier.fillMaxWidth().padding(top = MilkFlowSpacing.Small),
            label = { Text("Nombre") },
            singleLine = true,
            enabled = state.saveState != UsuariosSaveState.Saving,
        )
        Text(
            text = "Rol",
            modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
            style = MaterialTheme.typography.labelLarge,
        )
        RolUsuario.entries.forEach { rol ->
            SecondaryButton(
                text = if (state.rol == rol) "✓ ${rol.nombreVisible()}" else rol.nombreVisible(),
                onClick = { onEvent(UsuariosUiEvent.RolChanged(rol)) },
                modifier = Modifier.padding(top = MilkFlowSpacing.Small),
                enabled = state.saveState != UsuariosSaveState.Saving,
            )
        }
        Row(
            modifier = Modifier.fillMaxWidth().padding(top = MilkFlowSpacing.Medium),
            horizontalArrangement = Arrangement.SpaceBetween,
        ) {
            Text(
                text = if (state.activo) "Usuario activo" else "Usuario inactivo",
                color = MilkFlowColors.TextSecondary,
            )
            Switch(
                checked = state.activo,
                onCheckedChange = { onEvent(UsuariosUiEvent.ActivoChanged(it)) },
                enabled = state.saveState != UsuariosSaveState.Saving,
            )
        }
        val message = when (val saveState = state.saveState) {
            UsuariosSaveState.Idle, UsuariosSaveState.Saving -> null
            is UsuariosSaveState.Success -> saveState.message
            is UsuariosSaveState.Error -> saveState.message
        }
        if (message != null) {
            Text(
                text = message,
                modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
                color = if (state.saveState is UsuariosSaveState.Error) {
                    MilkFlowColors.Error
                } else {
                    MilkFlowColors.Success
                },
            )
        }
        PrimaryButton(
            text = if (state.saveState == UsuariosSaveState.Saving) "Guardando…" else "Guardar usuario",
            onClick = { onEvent(UsuariosUiEvent.Guardar) },
            enabled = state.formularioValido && state.saveState != UsuariosSaveState.Saving,
            modifier = Modifier.padding(top = MilkFlowSpacing.Medium),
        )
    }
}

private fun RolUsuario.nombreVisible(): String = when (this) {
    RolUsuario.ADMINISTRADORA -> "Administradora"
    RolUsuario.JEFE_PRODUCCION -> "Jefe de producción"
    RolUsuario.DESPACHO_QUESO -> "Personal de despacho de queso"
    RolUsuario.ACOPIADOR -> "Acopiador"
    RolUsuario.SUPERVISOR -> "Supervisor"
    RolUsuario.PENDIENTE_ASIGNACION -> "Pendiente de asignación"
}
