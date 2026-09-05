package pe.edu.upeu.milkflow.presentation.design

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.ui.unit.dp

private val MilkFlowColorScheme = lightColorScheme(
    primary = MilkFlowColors.Primary,
    onPrimary = MilkFlowColors.OnAction,
    primaryContainer = MilkFlowColors.PrimaryVariant,
    onPrimaryContainer = MilkFlowColors.OnAction,
    secondary = MilkFlowColors.Secondary,
    onSecondary = MilkFlowColors.OnAction,
    error = MilkFlowColors.Error,
    onError = Color.White,
    background = MilkFlowColors.Background,
    onBackground = MilkFlowColors.TextPrimary,
    surface = MilkFlowColors.Surface,
    onSurface = MilkFlowColors.TextPrimary,
    onSurfaceVariant = MilkFlowColors.TextSecondary,
    outline = MilkFlowColors.Border,
)

private val MilkFlowTypography = Typography(
    headlineSmall = TextStyle(
        fontWeight = FontWeight.Bold,
        fontSize = 24.sp,
        lineHeight = 30.sp,
    ),
    titleLarge = TextStyle(
        fontWeight = FontWeight.SemiBold,
        fontSize = 20.sp,
        lineHeight = 26.sp,
    ),
    titleMedium = TextStyle(
        fontWeight = FontWeight.SemiBold,
        fontSize = 16.sp,
        lineHeight = 22.sp,
    ),
    bodyLarge = TextStyle(
        fontSize = 16.sp,
        lineHeight = 24.sp,
    ),
    bodyMedium = TextStyle(
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
    labelLarge = TextStyle(
        fontWeight = FontWeight.SemiBold,
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
)

private val MilkFlowShapes = Shapes(
    small = RoundedCornerShape(8.dp),
    medium = RoundedCornerShape(14.dp),
    large = RoundedCornerShape(20.dp),
)

@Composable
fun MilkFlowTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = MilkFlowColorScheme,
        typography = MilkFlowTypography,
        shapes = MilkFlowShapes,
        content = content,
    )
}
