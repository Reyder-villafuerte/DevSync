package com.example.holamundo

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import com.example.holamundo.ui.pantallas.CatalogoPantalla
import com.example.holamundo.ui.pantallas.FormularioProducto
import com.example.holamundo.ui.pantallas.catalogoDemo

@Composable
fun App() {
    MaterialTheme {
        var productos by remember { mutableStateOf(catalogoDemo) }

        Column(
            modifier = Modifier.fillMaxSize()
        ) {
            FormularioProducto(
                alGuardar = { nuevo ->
                    productos = productos + nuevo
                }
            )

            HorizontalDivider()

            CatalogoPantalla(
                productos = productos,
                modifier = Modifier.weight(1f)
            )
        }
    }
}
