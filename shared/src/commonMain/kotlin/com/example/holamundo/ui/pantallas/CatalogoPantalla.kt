package com.example.holamundo.ui.pantallas

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import com.example.holamundo.domain.model.Categoria
import com.example.holamundo.domain.model.Producto

@Composable
fun Saludo(
    nombre: String,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxWidth()
            .padding(16.dp)
    ) {
        Text(
            text = "Hola, $nombre",
            style = MaterialTheme.typography.headlineSmall
        )
        Text("Bienvenido al catálogo")
    }
}

@Composable
fun PruebaModifiers() {
    Column(
        modifier = Modifier.padding(24.dp)
    ) {
        Text(
            text = "A: padding y luego background",
            modifier = Modifier
                .padding(16.dp)
                .background(Color(0xFFE0D5FF))
        )

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            text = "B: background y luego padding",
            modifier = Modifier
                .background(Color(0xFFE0D5FF))
                .padding(16.dp)
        )
    }
}

@Composable
fun Contador() {
    var clicks by remember { mutableStateOf(0) }

    Column(modifier = Modifier.padding(24.dp)) {
        Text("Pulsado $clicks veces")
        Spacer(modifier = Modifier.height(8.dp))
        Button(onClick = { clicks++ }) {
            Text("Sumar uno")
        }
    }
}

val catalogoDemo = listOf(
    Producto(
        id = "p-01",
        nombre = "Trucha frita",
        precio = 24.50,
        categoria = Categoria.PLATO_FONDO,
        descripcion = "Trucha del lago con papas doradas"
    ),
    Producto(
        id = "p-02",
        nombre = "Chairo paceño",
        precio = 15.50,
        categoria = Categoria.ENTRADA
    ),
    Producto(
        id = "p-03",
        nombre = "Lomo saltado",
        precio = 28.00,
        categoria = Categoria.PLATO_FONDO
    ),
    Producto(
        id = "p-04",
        nombre = "Chicha morada",
        precio = 8.00,
        categoria = Categoria.BEBIDA
    ),
    Producto(
        id = "p-05",
        nombre = "Emoliente",
        precio = 4.50,
        categoria = Categoria.BEBIDA,
        disponible = false
    ),
    Producto(
        id = "p-06",
        nombre = "Mazamorra morada",
        precio = 9.00,
        categoria = Categoria.POSTRE,
        descripcion = "Servida con arroz con leche"
    )
)

@Composable
fun CatalogoPantalla(
    productos: List<Producto> = catalogoDemo,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 8.dp)
    ) {
        Text(
            text = "Catálogo (${productos.size} productos)",
            style = MaterialTheme.typography.titleLarge,
            modifier = Modifier.padding(bottom = 8.dp)
        )

        LazyColumn(
            verticalArrangement = Arrangement.spacedBy(12.dp),
            contentPadding = PaddingValues(vertical = 8.dp)
        ) {
            items(productos, key = { it.id }) { producto ->
                TarjetaProducto(producto = producto)
            }
        }
    }
}