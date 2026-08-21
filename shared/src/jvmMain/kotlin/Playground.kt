import com.example.holamundo.domain.model.Categoria
import com.example.holamundo.domain.model.Producto

fun main() {

    // =========================================================
    // ACTIVIDAD 7 - INSTANCIAR PRODUCTOS
    // =========================================================

    // FORMA 1 - Posicional
    val trucha = Producto(
        "p-01",
        "Trucha frita",
        24.50,
        Categoria.PLATO_FONDO
    )

    // FORMA 2 - Parámetros con nombre
    val chicha = Producto(
        nombre = "Chicha morada",
        id = "p-04",
        precio = 8.00,
        categoria = Categoria.BEBIDA
    )

    // FORMA 3 - Todos los parámetros
    val chairo = Producto(
        id = "p-02",
        nombre = "Chairo paceño",
        precio = 15.50,
        categoria = Categoria.ENTRADA,
        descripcion = "Sopa tradicional con chuño, carne y verduras",
        disponible = true
    )

    // FORMA 4 - Producto agotado
    val emoliente = Producto(
        id = "p-05",
        nombre = "Emoliente",
        precio = 4.50,
        categoria = Categoria.BEBIDA,
        disponible = false
    )

    // =========================================================
    // LISTA DE PRODUCTOS
    // =========================================================

    val catalogo = listOf(
        trucha,
        chicha,
        chairo,
        emoliente
    )

    println("=== CATALOGO (${catalogo.size} productos) ===")

    catalogo.forEach { producto ->
        println(producto)
    }

    // =========================================================
    // REPORTE
    // =========================================================

    println()
    println("=== REPORTE ===")

    catalogo.forEach { p ->

        val estado =
            if (p.sePuedePedir) "DISPONIBLE"
            else "AGOTADO"

        println("[$estado] ${p.nombre} - ${p.precioFormateado}")
        println(" ${p.descripcionCorta}")
    }

    println()

    println("Pedibles: ${catalogo.count { it.sePuedePedir }}")

    println(
        "Suma de precios: ${
            catalogo.sumOf { it.precio }
        }"
    )

    println(
        "Mas caro: ${
            catalogo.maxByOrNull { it.precio }?.nombre
        }"
    )

    println(
        "Solo bebidas: ${
            catalogo
                .filter { it.categoria == Categoria.BEBIDA }
                .map { it.nombre }
        }"
    )

    println(
        "Por categoria: ${
            catalogo
                .groupBy { it.categoria }
                .mapValues { it.value.size }
        }"
    )


    // =========================================================
    // ACTIVIDAD 8 - DATA CLASS
    // copy(), igualdad y desestructuración
    // =========================================================

    println()
    println("=== DATA CLASS ===")

    // 1. COPY
    // Crea una copia de trucha cambiando solamente disponible.
    val truchaAgotada = trucha.copy(
        disponible = false
    )

    println("Original: $trucha")
    println("Copia: $truchaAgotada")


    // 2. IGUALDAD
    // Como Producto es data class, == compara sus datos.
    val otraTrucha = trucha.copy()

    println(
        "trucha == otraTrucha: ${
            trucha == otraTrucha
        }"
    )


    // 3. DESESTRUCTURACIÓN
    // Extraemos las primeras propiedades del objeto.
    val (id, nombre, precio) = trucha

    println("ID: $id")
    println("Nombre: $nombre")
    println("Precio: $precio")
}