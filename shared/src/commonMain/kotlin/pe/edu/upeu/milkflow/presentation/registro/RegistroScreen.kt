package pe.edu.upeu.milkflow.presentation.registro

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.focus.FocusDirection
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.presentation.components.PrimaryButton
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun RegistroScreen(
    state: RegistroUiState,
    onEvent: (RegistroUiEvent) -> Unit,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val focusManager = LocalFocusManager.current
    val isLoading = state.submission == RegistroSubmission.Loading
    val errorMessage = (state.submission as? RegistroSubmission.Error)?.message
    val isSuccess = state.submission == RegistroSubmission.Success

    var passwordVisible by remember { mutableStateOf(false) }
    var confirmPasswordVisible by remember { mutableStateOf(false) }

    LaunchedEffect(isSuccess) {
        if (isSuccess) {
            onNavigateBack()
        }
    }

    Box(
        modifier = modifier
            .fillMaxSize()
            .background(MilkFlowColors.Background)
            .verticalScroll(rememberScrollState())
            .padding(MilkFlowSpacing.Large),
        contentAlignment = Alignment.Center,
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.fillMaxWidth()
        ) {
            // Header
            Text(
                text = "Crear cuenta",
                style = MaterialTheme.typography.headlineMedium,
                color = MilkFlowColors.Primary,
                fontWeight = FontWeight.Bold
            )
            Spacer(Modifier.height(MilkFlowSpacing.Small))
            Text(
                text = "Únete a la red de acopio inteligente",
                style = MaterialTheme.typography.bodyLarge,
                color = MilkFlowColors.TextSecondary,
                textAlign = TextAlign.Center
            )

            Spacer(Modifier.height(MilkFlowSpacing.Large))

            // Card
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .shadow(elevation = 8.dp, shape = RoundedCornerShape(24.dp)),
                colors = CardDefaults.cardColors(containerColor = Color.White),
                shape = RoundedCornerShape(24.dp)
            ) {
                Column(
                    modifier = Modifier.padding(MilkFlowSpacing.Large),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    OutlinedTextField(
                        value = state.nombreCompleto,
                        onValueChange = { onEvent(RegistroUiEvent.NombreCompletoChanged(it)) },
                        label = { Text("Nombre completo") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        enabled = !isLoading,
                        shape = RoundedCornerShape(12.dp),
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Next),
                        keyboardActions = KeyboardActions(onNext = { focusManager.moveFocus(FocusDirection.Down) })
                    )

                    Spacer(Modifier.height(MilkFlowSpacing.Medium))

                    OutlinedTextField(
                        value = state.correo,
                        onValueChange = { onEvent(RegistroUiEvent.CorreoChanged(it)) },
                        label = { Text("Correo electrónico") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        enabled = !isLoading,
                        shape = RoundedCornerShape(12.dp),
                        keyboardOptions = KeyboardOptions(
                            keyboardType = KeyboardType.Email,
                            imeAction = ImeAction.Next
                        ),
                        keyboardActions = KeyboardActions(onNext = { focusManager.moveFocus(FocusDirection.Down) })
                    )

                    Spacer(Modifier.height(MilkFlowSpacing.Medium))

                    OutlinedTextField(
                        value = state.clave,
                        onValueChange = { onEvent(RegistroUiEvent.ClaveChanged(it)) },
                        label = { Text("Contraseña") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        enabled = !isLoading,
                        shape = RoundedCornerShape(12.dp),
                        visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                        trailingIcon = {
                            Text(
                                text = if (passwordVisible) "🙈" else "👁",
                                modifier = Modifier
                                    .padding(end = 8.dp)
                                    .clickable { passwordVisible = !passwordVisible }
                            )
                        },
                        keyboardOptions = KeyboardOptions(
                            keyboardType = KeyboardType.Password,
                            imeAction = ImeAction.Next
                        ),
                        keyboardActions = KeyboardActions(onNext = { focusManager.moveFocus(FocusDirection.Down) })
                    )

                    Spacer(Modifier.height(MilkFlowSpacing.Medium))

                    OutlinedTextField(
                        value = state.confirmarClave,
                        onValueChange = { onEvent(RegistroUiEvent.ConfirmarClaveChanged(it)) },
                        label = { Text("Confirmar contraseña") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        enabled = !isLoading,
                        shape = RoundedCornerShape(12.dp),
                        visualTransformation = if (confirmPasswordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                        trailingIcon = {
                            Text(
                                text = if (confirmPasswordVisible) "🙈" else "👁",
                                modifier = Modifier
                                    .padding(end = 8.dp)
                                    .clickable { confirmPasswordVisible = !confirmPasswordVisible }
                            )
                        },
                        keyboardOptions = KeyboardOptions(
                            keyboardType = KeyboardType.Password,
                            imeAction = ImeAction.Done
                        ),
                        keyboardActions = KeyboardActions(onDone = {
                            focusManager.clearFocus()
                            onEvent(RegistroUiEvent.Registrar)
                        })
                    )

                    if (errorMessage != null) {
                        Spacer(Modifier.height(MilkFlowSpacing.Medium))
                        Text(
                            text = errorMessage,
                            color = MilkFlowColors.Error,
                            style = MaterialTheme.typography.bodySmall,
                            textAlign = TextAlign.Center
                        )
                    }

                    Spacer(Modifier.height(MilkFlowSpacing.Large))

                    PrimaryButton(
                        text = if (isLoading) "Creando cuenta..." else "Crear cuenta",
                        onClick = {
                            focusManager.clearFocus()
                            onEvent(RegistroUiEvent.Registrar)
                        },
                        enabled = !isLoading && state.formularioValido
                    )
                }
            }

            Spacer(Modifier.height(MilkFlowSpacing.Large))

            // Footer
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.Center
            ) {
                Text(
                    text = "¿Ya tienes una cuenta?",
                    color = MilkFlowColors.TextSecondary,
                    style = MaterialTheme.typography.bodyMedium
                )
                Text(
                    text = " Iniciar sesión",
                    color = MilkFlowColors.Secondary,
                    fontWeight = FontWeight.Bold,
                    style = MaterialTheme.typography.bodyMedium,
                    modifier = Modifier.clickable { onNavigateBack() }
                )
            }
        }
    }
}
