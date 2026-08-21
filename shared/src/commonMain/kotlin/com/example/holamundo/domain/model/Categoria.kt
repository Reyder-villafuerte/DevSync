package com.example.holamundo.domain.model

/**
 * Categorías del catálogo.
 * Un enum sirve cuando las opciones son fijas y ninguna lleva datos propios.
 */
enum class Categoria(val etiqueta: String) {
    ENTRADA("Entradas"),
    PLATO_FONDO("Platos de fondo"),
    BEBIDA("Bebidas"),
    POSTRE("Postres")
}