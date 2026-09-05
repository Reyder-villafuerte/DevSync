package pe.edu.upeu.milkflow.presentation.login

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
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
import androidx.compose.ui.unit.sp
import pe.edu.upeu.milkflow.presentation.components.PrimaryButton
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun LoginScreen(
    state: LoginUiState,
    onEvent: (LoginUiEvent) -> Unit,
    onNavigateToRegistro: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val focusManager = LocalFocusManager.current
    val isLoading = state.submission == LoginSubmission.Loading
    val errorMessage = (state.submission as? LoginSubmission.Error)?.message

    var passwordVisible by remember { mutableStateOf(false) }

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
            // Logo y Título Superior
            Box(
                modifier = Modifier
                    .size(80.dp)
                    .background(MilkFlowColors.Primary, CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = "🥛",
                    fontSize = 40.sp
                )
            }
            Spacer(Modifier.height(MilkFlowSpacing.Medium))
            Text(
                text = "MilkFlow",
                style = MaterialTheme.typography.displaySmall,
                color = MilkFlowColors.Primary,
                fontWeight = FontWeight.ExtraBold
            )
            Text(
                text = "Gestión inteligente del acopio de leche",
                style = MaterialTheme.typography.bodyMedium,
                color = MilkFlowColors.TextSecondary,
                textAlign = TextAlign.Center
            )

            Spacer(Modifier.height(MilkFlowSpacing.XLarge))

            // Tarjeta Blanca Central
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
                        value = state.nombreUsuario,
                        onValueChange = { onEvent(LoginUiEvent.NombreUsuarioChanged(it)) },
                        label = { Text("Correo o usuario") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        enabled = !isLoading,
                        shape = RoundedCornerShape(12.dp),
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Next),
                        keyboardActions = KeyboardActions(onNext = { focusManager.moveFocus(FocusDirection.Down) })
                    )

                    Spacer(Modifier.height(MilkFlowSpacing.Medium))

                    OutlinedTextField(
                        value = state.clave,
                        onValueChange = { onEvent(LoginUiEvent.ClaveChanged(it)) },
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
                            imeAction = ImeAction.Done
                        ),
                        keyboardActions = KeyboardActions(onDone = {
                            focusManager.clearFocus()
                            onEvent(LoginUiEvent.IniciarSesion)
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
                        text = if (isLoading) "Iniciando sesión..." else "Iniciar sesión",
                        onClick = {
                            focusManager.clearFocus()
                            onEvent(LoginUiEvent.IniciarSesion)
                        },
                        enabled = !isLoading
                    )

                    Spacer(Modifier.height(MilkFlowSpacing.Medium))

                    Text(
                        text = "¿Olvidaste tu contraseña?",
                        color = MilkFlowColors.Primary,
                        fontWeight = FontWeight.SemiBold,
                        style = MaterialTheme.typography.bodySmall,
                        modifier = Modifier.clickable { 
                            // Opcional: mostrar mensaje de "Función pendiente"
                        }
                    )
                }
            }

            Spacer(Modifier.height(MilkFlowSpacing.XLarge))

            // Footer inferior
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.Center
            ) {
                Text(
                    text = "¿No tienes una cuenta?",
                    color = MilkFlowColors.TextSecondary,
                    style = MaterialTheme.typography.bodyMedium
                )
                Text(
                    text = " Crear cuenta",
                    color = MilkFlowColors.Secondary,
                    fontWeight = FontWeight.Bold,
                    style = MaterialTheme.typography.bodyMedium,
                    modifier = Modifier.clickable { onNavigateToRegistro() }
                )
            }
        }
    }
}
