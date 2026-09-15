package com.example.milkflowmovil.ui.componentes

import androidx.compose.foundation.Image
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.IntSize
import org.jetbrains.compose.resources.painterResource
import org.jetbrains.compose.resources.imageResource
import milkflowmovil.shared.generated.resources.*

@Composable fun MarcaHuata() {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Image(painterResource(Res.drawable.huata_vaca), "Símbolo de Huata", Modifier.size(76.dp))
        Spacer(Modifier.width(12.dp))
        Image(painterResource(Res.drawable.huata_letras), "Ecolácteos Huata", Modifier.width(155.dp).height(70.dp))
    }
}

@Composable fun DibujoHuata(titulo: String) {
    val imagen = imageResource(Res.drawable.huata_dibujos)
    val texto = titulo.lowercase()
    val celda = when {
        "ques" in texto || "molde" in texto -> 3
        "productor" in texto || "persona" in texto -> 1
        "pago" in texto || "saldo" in texto || "venta" in texto || "soles" in texto -> 2
        "ruta" in texto || "zona" in texto -> 5
        "recibo" in texto || "historial" in texto -> 4
        else -> 0
    }
    Canvas(Modifier.size(48.dp)) {
        val ancho = imagen.width / 3
        val alto = imagen.height / 2
        drawImage(imagen, srcOffset = IntOffset(celda % 3 * ancho, celda / 3 * alto), srcSize = IntSize(ancho, alto), dstSize = IntSize(size.width.toInt(), size.height.toInt()))
    }
}
