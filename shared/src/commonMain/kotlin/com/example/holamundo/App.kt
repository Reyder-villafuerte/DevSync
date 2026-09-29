package com.example.holamundo

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.safeContentPadding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import org.jetbrains.compose.resources.painterResource

import holamundo.shared.generated.resources.Res
import holamundo.shared.generated.resources.compose_multiplatform

@Composable
@Preview
fun App() {
    MaterialTheme {
        var showContent by remember { mutableStateOf(false) }
        Column(
            modifier = Modifier
                .background(MaterialTheme.colorScheme.primaryContainer)
                .safeContentPadding()
                .fillMaxSize(),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Button(onClick = { showContent = !showContent }) {
                Text("Reyder")
            }
            Text(
                text = "¡Holaaaaaaaaaaa!",
                style = MaterialTheme.typography.headlineLarge
            )
            Text(
                text = "¡Mi primera aplicación!",
                style = MaterialTheme.typography.headlineLarge
            )
            AnimatedVisibility(showContent) {
                val greeting = remember { Greeting().greet() }
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalAlignment = Alignment.CenterHorizontally,
                ) {
                    Image(painterResource(Res.drawable.compose_multiplatform), null)
                    Text("Compose: $greeting")
                }
            }
        }
    }
}

fun main() {
    val nombreProducto: String = "Trucha frita"
    val precioUnitario: Double = 24.50
    val stockDisponible: Int = 12
    val estaActivo: Boolean = true
    val inicial: Char = 'T'

    val categoria = "Platos de fondo"
    val descuento = 0.15
    val vendidosHoy = 7

    println("Producto: $nombreProducto")
    println("Precio: S/ $precioUnitario")
    println("Stock: $stockDisponible unidades")
    println("Activo: $estaActivo | Inicial: $inicial")
    println("Categoria: $categoria | Descuento: $descuento")
    println("Total del dia: ${precioUnitario * vendidosHoy}")
}