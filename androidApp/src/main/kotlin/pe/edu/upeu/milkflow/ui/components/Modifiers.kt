package pe.edu.upeu.milkflow.ui.components

import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.sizeIn
import androidx.compose.ui.Modifier
import pe.edu.upeu.milkflow.ui.theme.Dimens

/** Garantiza el objetivo táctil mínimo de 48dp en cualquier elemento interactivo. */
fun Modifier.objetivoTactil(): Modifier = this.heightIn(min = Dimens.ObjetivoTactilMin)

fun Modifier.objetivoTactilCuadrado(): Modifier =
    this.sizeIn(minWidth = Dimens.ObjetivoTactilMin, minHeight = Dimens.ObjetivoTactilMin)
