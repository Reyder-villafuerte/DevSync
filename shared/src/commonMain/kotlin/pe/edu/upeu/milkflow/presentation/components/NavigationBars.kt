package pe.edu.upeu.milkflow.presentation.components

import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.text.font.FontWeight
import pe.edu.upeu.milkflow.presentation.design.MilkFlowColors

data class MilkFlowBottomItem(
    val route: String,
    val label: String,
    val symbol: String,
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MilkFlowTopBar(
    title: String,
    canNavigateBack: Boolean,
    onNavigateBack: () -> Unit,
) {
    TopAppBar(
        title = { Text(title, fontWeight = FontWeight.SemiBold) },
        navigationIcon = {
            if (canNavigateBack) {
                TextButton(onClick = onNavigateBack) {
                    Text("Atrás")
                }
            }
        },
        colors = TopAppBarDefaults.topAppBarColors(
            containerColor = MilkFlowColors.Surface,
            titleContentColor = MilkFlowColors.TextPrimary,
            navigationIconContentColor = MilkFlowColors.Primary,
        ),
    )
}

@Composable
fun MilkFlowBottomBar(
    items: List<MilkFlowBottomItem>,
    currentRoute: String?,
    onSelect: (MilkFlowBottomItem) -> Unit,
) {
    NavigationBar(containerColor = MilkFlowColors.Surface) {
        items.forEach { item ->
            val selected = currentRoute == item.route
            NavigationBarItem(
                selected = selected,
                onClick = { onSelect(item) },
                icon = {
                    Text(
                        text = item.symbol,
                        style = MaterialTheme.typography.titleMedium,
                    )
                },
                label = { Text(item.label) },
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = MilkFlowColors.Primary,
                    selectedTextColor = MilkFlowColors.Primary,
                    indicatorColor = MilkFlowColors.Primary.copy(alpha = 0.12f),
                    unselectedIconColor = MilkFlowColors.TextSecondary,
                    unselectedTextColor = MilkFlowColors.TextSecondary,
                ),
            )
        }
    }
}
