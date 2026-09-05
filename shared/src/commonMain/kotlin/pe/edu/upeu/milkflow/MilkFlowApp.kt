package pe.edu.upeu.milkflow

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import pe.edu.upeu.milkflow.presentation.design.MilkFlowTheme
import pe.edu.upeu.milkflow.presentation.navigation.MilkFlowNavigation

@Composable
fun MilkFlowApp() {
    MilkFlowTheme {
        Surface(modifier = Modifier.fillMaxSize()) {
            MilkFlowNavigation()
        }
    }
}
