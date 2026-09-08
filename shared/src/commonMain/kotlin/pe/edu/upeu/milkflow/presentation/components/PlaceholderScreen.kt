package pe.edu.upeu.milkflow.presentation.components

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun PlaceholderScreen(
    title: String,
    message: String = "Módulo en desarrollo para la siguiente etapa.",
    modifier: Modifier = Modifier
) {
    Box(
        modifier = modifier.fillMaxSize().padding(MilkFlowSpacing.Large),
        contentAlignment = Alignment.Center
    ) {
        EmptyState(
            title = title,
            message = message
        )
    }
}
