package pe.edu.upeu.milkflow.presentation.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSizes
import pe.edu.upeu.milkflow.presentation.design.MilkFlowSpacing

@Composable
fun MilkFlowCard(
    modifier: Modifier = Modifier,
    contentPadding: PaddingValues = PaddingValues(MilkFlowSpacing.Medium),
    content: @Composable ColumnScope.() -> Unit,
) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = MilkFlowSizes.CardElevation),
        shape = MaterialTheme.shapes.medium,
    ) {
        Column(
            modifier = Modifier.padding(contentPadding),
            content = content,
        )
    }
}

@Composable
fun PrimaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    Button(
        onClick = onClick,
        modifier = modifier
            .fillMaxWidth()
            .height(MilkFlowSizes.ButtonHeight),
        enabled = enabled,
        colors = ButtonDefaults.buttonColors(
            containerColor = MilkFlowColors.Secondary,
            contentColor = MilkFlowColors.OnAction,
        ),
        shape = MaterialTheme.shapes.medium,
    ) {
        Text(text)
    }
}

@Composable
fun SecondaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    OutlinedButton(
        onClick = onClick,
        modifier = modifier
            .fillMaxWidth()
            .height(MilkFlowSizes.ButtonHeight),
        enabled = enabled,
        border = BorderStroke(1.dp, MilkFlowColors.Primary),
        colors = ButtonDefaults.outlinedButtonColors(contentColor = MilkFlowColors.Primary),
        shape = MaterialTheme.shapes.medium,
    ) {
        Text(text)
    }
}

@Composable
fun SectionTitle(
    title: String,
    modifier: Modifier = Modifier,
    supportingText: String? = null,
) {
    Column(modifier = modifier.fillMaxWidth()) {
        Text(
            text = title,
            style = MaterialTheme.typography.titleLarge,
            color = MilkFlowColors.TextPrimary,
        )
        if (supportingText != null) {
            Spacer(Modifier.height(MilkFlowSpacing.XSmall))
            Text(
                text = supportingText,
                style = MaterialTheme.typography.bodyMedium,
                color = MilkFlowColors.TextSecondary,
            )
        }
    }
}

enum class StatusTone {
    SUCCESS,
    WARNING,
    ERROR,
    NEUTRAL,
}

@Composable
fun StatusChip(
    text: String,
    tone: StatusTone,
    modifier: Modifier = Modifier,
) {
    val color = when (tone) {
        StatusTone.SUCCESS -> MilkFlowColors.Success
        StatusTone.WARNING -> MilkFlowColors.Warning
        StatusTone.ERROR -> MilkFlowColors.Error
        StatusTone.NEUTRAL -> MilkFlowColors.TextSecondary
    }
    Surface(
        modifier = modifier,
        color = color.copy(alpha = 0.12f),
        contentColor = color,
        shape = CircleShape,
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 7.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                Modifier
                    .size(MilkFlowSizes.StatusDot)
                    .background(color, CircleShape),
            )
            Text(
                text = text,
                modifier = Modifier.padding(start = MilkFlowSpacing.Small),
                style = MaterialTheme.typography.labelLarge,
            )
        }
    }
}
