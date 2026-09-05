package pe.edu.upeu.milkflow.presentation.components

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun SectionShell(
    title: String,
    description: String,
    modifier: Modifier = Modifier,
    actionText: String? = null,
    onAction: (() -> Unit)? = null,
    secondaryActionText: String? = null,
    onSecondaryAction: (() -> Unit)? = null,
) {
    LazyColumn(
        modifier = modifier
            .fillMaxSize()
            .padding(MilkFlowSpacing.Medium),
        verticalArrangement = Arrangement.spacedBy(MilkFlowSpacing.Medium),
    ) {
        item {
            SectionTitle(title = title, supportingText = description)
        }
        item {
            MilkFlowCard(modifier = Modifier.fillMaxWidth()) {
                Text(
                    text = "Esta sección está preparada para incorporar su flujo funcional en una etapa posterior.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MilkFlowColors.TextSecondary,
                )
            }
        }
        if (actionText != null && onAction != null) {
            item {
                PrimaryButton(text = actionText, onClick = onAction)
            }
        }
        if (secondaryActionText != null && onSecondaryAction != null) {
            item {
                SecondaryButton(text = secondaryActionText, onClick = onSecondaryAction)
            }
        }
    }
}
