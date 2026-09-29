package com.example.milkflowmovil.ui.pantallas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.FilterChip
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.contieneTexto
import com.example.milkflowmovil.core.soles
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.dominio.Cliente
import com.example.milkflowmovil.dominio.Pantalla
import com.example.milkflowmovil.dominio.Reglas
import com.example.milkflowmovil.dominio.Venta
import com.example.milkflowmovil.ui.Navegador
import com.example.milkflowmovil.ui.componentes.BotonPrimario
import com.example.milkflowmovil.ui.componentes.BotonSecundario
import com.example.milkflowmovil.ui.componentes.Campo
import com.example.milkflowmovil.ui.componentes.EncabezadoSeccion
import com.example.milkflowmovil.ui.componentes.Etiqueta
import com.example.milkflowmovil.ui.componentes.FilaDato
import com.example.milkflowmovil.ui.componentes.MensajeVacio
import com.example.milkflowmovil.ui.componentes.Nota
import com.example.milkflowmovil.ui.componentes.Tarjeta
import com.example.milkflowmovil.ui.componentes.TarjetaMetrica
import com.example.milkflowmovil.ui.tema.coloresMilkFlow

/**
 * Mostrador de venta de queso: ventas del día, arqueo y cierre de caja.
 *
 * El efectivo (dinero físico) y el descuento a cuenta de leche se cuentan por
 * separado: no es lo mismo lo que hay en la caja que lo que se le rebajará al
 * proveedor en su liquidación.
 */
@Composable
fun PantallaVentas(repositorio: Repositorio, estado: EstadoLocal, navegador: Navegador) {
    val hoy = Fechas.hoy()
    var mostrarArqueo by remember { mutableStateOf(false) }
    var mensaje by remember { mutableStateOf<String?>(null) }
    var error by remember { mutableStateOf<String?>(null) }

    val ventasHoy = estado.ventas
        .filter { it.vendidoEn.take(10) == hoy && it.cierreId == null }
        .sortedByDescending { it.vendidoEn }

    val efectivo = ventasHoy.filterNot { it.esDescuentoLeche }.sumOf { it.total }
    val aCuenta = ventasHoy.filter { it.esDescuentoLeche }.sumOf { it.total }
    val moldes = ventasHoy.sumOf { it.moldes }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Efectivo en caja", efectivo.soles(), "dinero físico", coloresMilkFlow.exito, Modifier.weight(1f))
                TarjetaMetrica("A cuenta de leche", aCuenta.soles(), "se descuenta al proveedor", coloresMilkFlow.aviso, Modifier.weight(1f))
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Moldes despachados", "$moldes", "hoy", modifier = Modifier.weight(1f))
                TarjetaMetrica("Stock de queso", "${estado.stockQueso.toInt()}", "moldes disponibles", modifier = Modifier.weight(1f))
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                BotonPrimario("+ Nueva venta", Modifier.weight(1f)) { navegador.ir(Pantalla.NUEVA_VENTA) }
                BotonSecundario(if (mostrarArqueo) "Ocultar arqueo" else "Cierre de caja", Modifier.weight(1f)) {
                    mostrarArqueo = !mostrarArqueo
                }
            }
        }

        if (mostrarArqueo) {
            item {
                Tarjeta {
                    Text("Arqueo del día", style = MaterialTheme.typography.titleMedium)
                    Spacer(Modifier.height(8.dp))

                    listOf(
                        "proveedor" to "Proveedores de leche",
                        "mayorista" to "Clientes mayoristas",
                        "local" to "Clientes locales",
                    ).forEach { (tipo, nombre) ->
                        val delTipo = ventasHoy.filter { venta ->
                            (estado.cliente(venta.clienteId)?.type ?: "local") == tipo
                        }
                        FilaDato(
                            "$nombre (${delTipo.sumOf { it.moldes }} moldes)",
                            delTipo.sumOf { it.total }.soles(),
                        )
                    }

                    Spacer(Modifier.height(8.dp))
                    HorizontalDivider(color = coloresMilkFlow.borde)
                    Spacer(Modifier.height(8.dp))

                    FilaDato("Efectivo real en caja", efectivo.soles(), resaltado = true, color = coloresMilkFlow.exito)
                    FilaDato("A cuenta de leche", aCuenta.soles(), color = coloresMilkFlow.aviso)
                    FilaDato("Total del día", (efectivo + aCuenta).soles(), resaltado = true)
                    FilaDato("Transacciones", "${ventasHoy.size}")

                    if (error != null) {
                        Spacer(Modifier.height(10.dp))
                        Nota(error!!, coloresMilkFlow.peligro, "⚠️")
                    }

                    if (mensaje != null) {
                        Spacer(Modifier.height(10.dp))
                        Nota(mensaje!!, coloresMilkFlow.exito, "✅")
                    }

                    Spacer(Modifier.height(12.dp))
                    BotonPrimario("Cerrar caja del día", Modifier.fillMaxWidth()) {
                        error = null
                        mensaje = null
                        val fallo = repositorio.cerrarCaja(null)
                        if (fallo != null) error = fallo.mensaje
                        else mensaje = "Caja cerrada. La lista de ventas de hoy vuelve a quedar en blanco."
                    }
                }
            }
        }

        item { EncabezadoSeccion("Ventas de hoy", "Se vacía al cerrar la caja.") }

        if (ventasHoy.isEmpty()) {
            item { MensajeVacio("Todavía no hay ventas registradas hoy.", "🛒") }
        }

        items(ventasHoy, key = { it.id }) { venta ->
            FilaVenta(venta, estado) { navegador.ir(Pantalla.RECIBO_DETALLE, venta.id) }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

@Composable
private fun FilaVenta(venta: Venta, estado: EstadoLocal, alPulsar: () -> Unit) {
    val cliente = estado.cliente(venta.clienteId)

    Tarjeta(alPulsar = alPulsar) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(
                    cliente?.nombreCompleto ?: "Cliente de mostrador",
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    "${venta.recibo} · ${venta.moldes} moldes × ${venta.precioUnitario.soles()}",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )
            }
            Column(horizontalAlignment = Alignment.End) {
                Text(venta.total.soles(), fontWeight = FontWeight.Black)
                Etiqueta(
                    if (venta.esDescuentoLeche) "A cuenta" else "Efectivo",
                    if (venta.esDescuentoLeche) coloresMilkFlow.aviso else coloresMilkFlow.exito,
                )
                if (venta.pendiente) {
                    Spacer(Modifier.height(4.dp))
                    Etiqueta("Sin subir", coloresMilkFlow.info)
                }
            }
        }
    }
}

/** Alta de venta con tarifa automática según el tipo de cliente y la cantidad. */
@Composable
fun PantallaNuevaVenta(repositorio: Repositorio, estado: EstadoLocal, navegador: Navegador) {
    var busqueda by remember { mutableStateOf("") }
    var clienteId by remember { mutableStateOf<Long?>(null) }
    var clienteNuevo by remember { mutableStateOf(false) }
    var nombres by remember { mutableStateOf("") }
    var apellidos by remember { mutableStateOf("") }
    var dni by remember { mutableStateOf("") }
    var tipo by remember { mutableStateOf("local") }
    var moldes by remember { mutableStateOf("1") }
    var formaPago by remember { mutableStateOf("efectivo") }
    var error by remember { mutableStateOf<String?>(null) }

    val cantidad = moldes.toIntOrNull() ?: 0
    val cliente = estado.cliente(clienteId)
    val precio = Reglas.precioQueso(cliente, cantidad, estado.tarifaVigente)
    val puedeDescontar = cliente?.usuarioVinculadoId != null

    val coincidencias = estado.clientes
        .filter { it.nombreCompleto.contieneTexto(busqueda) || (it.dniRuc ?: "").contains(busqueda) }
        .take(8)

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Tarjeta {
            Text("Cliente", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(10.dp))

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(!clienteNuevo, { clienteNuevo = false }, { Text("Registrado") })
                FilterChip(clienteNuevo, { clienteNuevo = true; clienteId = null }, { Text("Nuevo") })
            }

            Spacer(Modifier.height(12.dp))

            if (clienteNuevo) {
                Campo(nombres, "Nombres", { nombres = it })
                Spacer(Modifier.height(8.dp))
                Campo(apellidos, "Apellidos", { apellidos = it })
                Spacer(Modifier.height(8.dp))
                Campo(dni, "DNI o RUC (opcional)", { dni = it })
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf("local" to "Local", "mayorista" to "Mayorista", "proveedor" to "Proveedor").forEach { (clave, texto) ->
                        FilterChip(tipo == clave, { tipo = clave }, { Text(texto) })
                    }
                }
            } else {
                Campo(busqueda, "Buscar por apellido o DNI", { busqueda = it })
                Spacer(Modifier.height(8.dp))

                coincidencias.forEach { candidato ->
                    FilaCliente(candidato, candidato.id == clienteId) {
                        clienteId = if (clienteId == candidato.id) null else candidato.id
                        if (clienteId == null || candidato.usuarioVinculadoId == null) formaPago = "efectivo"
                    }
                }

                if (coincidencias.isEmpty()) {
                    Text(
                        "Sin coincidencias. Usa la pestaña «Nuevo» para registrarlo.",
                        style = MaterialTheme.typography.bodySmall,
                        color = coloresMilkFlow.textoSuave,
                    )
                }
            }
        }

        Tarjeta {
            Text("Venta", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(10.dp))

            Campo(moldes, "Moldes de queso", { moldes = it; error = null }, numerico = true)

            Spacer(Modifier.height(10.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(formaPago == "efectivo", { formaPago = "efectivo" }, { Text("Efectivo") })
                FilterChip(
                    formaPago == "descuento_leche",
                    { if (puedeDescontar) formaPago = "descuento_leche" },
                    { Text("A cuenta de leche") },
                    enabled = puedeDescontar,
                )
            }

            if (!puedeDescontar) {
                Spacer(Modifier.height(6.dp))
                Text(
                    "El descuento a cuenta de leche solo aplica a proveedores registrados de la asociación.",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )
            }

            Spacer(Modifier.height(12.dp))
            FilaDato("Tarifa aplicada", precio.soles())
            FilaDato("Moldes", "$cantidad")
            FilaDato("Total a cobrar", (precio * cantidad).soles(), resaltado = true, color = coloresMilkFlow.acento)
            FilaDato("Stock después de la venta", "${(estado.stockQueso - cantidad).toInt()} moldes")

            if (error != null) {
                Spacer(Modifier.height(10.dp))
                Nota(error!!, coloresMilkFlow.peligro, "⚠️")
            }

            Spacer(Modifier.height(12.dp))
            BotonPrimario("Registrar venta y emitir recibo", Modifier.fillMaxWidth(), habilitado = cantidad > 0) {
                val fallo = repositorio.registrarVenta(
                    clienteId = clienteId,
                    nuevoNombre = nombres,
                    nuevoApellido = apellidos,
                    nuevoDni = dni,
                    nuevoTipo = if (clienteNuevo) tipo else null,
                    moldes = cantidad,
                    formaPago = formaPago,
                )

                if (fallo != null) error = fallo.mensaje else navegador.volver()
            }

            Spacer(Modifier.height(8.dp))
            Nota(
                "Sin señal la venta se guarda igual; el número de recibo lo asigna la planta al sincronizar.",
                coloresMilkFlow.info, "📶",
            )
        }
    }
}

@Composable
private fun FilaCliente(cliente: Cliente, seleccionado: Boolean, alPulsar: () -> Unit) {
    Row(
        Modifier.fillMaxWidth().padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Column(Modifier.weight(1f)) {
            Text(cliente.nombreCompleto, style = MaterialTheme.typography.bodyMedium)
            Text(
                "${cliente.type.replaceFirstChar { it.uppercase() }} · ${cliente.dniRuc ?: "sin documento"}",
                style = MaterialTheme.typography.bodySmall,
                color = coloresMilkFlow.textoSuave,
            )
        }
        BotonSecundario(if (seleccionado) "Elegido" else "Elegir") { alPulsar() }
    }
}

/** Histórico de recibos emitidos. */
@Composable
fun PantallaRecibos(repositorio: Repositorio, estado: EstadoLocal, navegador: Navegador) {
    var busqueda by remember { mutableStateOf("") }

    val ventas = estado.ventas
        .filter { venta ->
            val cliente = estado.cliente(venta.clienteId)
            venta.recibo.contieneTexto(busqueda) || (cliente?.nombreCompleto ?: "").contieneTexto(busqueda)
        }
        .sortedByDescending { it.vendidoEn }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Recibos", "${estado.ventas.size}", "emitidos", modifier = Modifier.weight(1f))
                TarjetaMetrica(
                    "Recaudado en efectivo",
                    estado.ventas.filterNot { it.esDescuentoLeche }.sumOf { it.total }.soles(),
                    "histórico",
                    coloresMilkFlow.exito,
                    Modifier.weight(1f),
                )
            }
        }

        item { Campo(busqueda, "Buscar por recibo o cliente", { busqueda = it }) }

        if (ventas.isEmpty()) {
            item { MensajeVacio("No hay recibos que coincidan.", "📄") }
        }

        items(ventas, key = { it.id }) { venta ->
            FilaVenta(venta, estado) { navegador.ir(Pantalla.RECIBO_DETALLE, venta.id) }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

/** Comprobante para mostrar al cliente. */
@Composable
fun PantallaRecibo(estado: EstadoLocal, ventaId: Long?) {
    val venta = estado.ventas.firstOrNull { it.id == ventaId }

    if (venta == null) {
        MensajeVacio("No se encontró el recibo.", "📄")
        return
    }

    val cliente = estado.cliente(venta.clienteId)
    val vendedor = estado.usuario(venta.vendedorId)

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Tarjeta {
            Column(Modifier.fillMaxWidth(), horizontalAlignment = Alignment.CenterHorizontally) {
                Text("ASOCIACIÓN DE PRODUCTORES", style = MaterialTheme.typography.labelMedium)
                Text("MILKFLOW HUATA", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Black)
                Text(
                    "Comprobante de venta de queso",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                    textAlign = TextAlign.Center,
                )
            }

            Spacer(Modifier.height(14.dp))
            HorizontalDivider(color = coloresMilkFlow.borde)
            Spacer(Modifier.height(14.dp))

            FilaDato("Recibo", venta.recibo, resaltado = true)
            FilaDato("Fecha", "${Fechas.corta(venta.vendidoEn)} ${Fechas.horaCorta(venta.vendidoEn)}")
            FilaDato("Cliente", cliente?.nombreCompleto ?: "Cliente de mostrador")
            FilaDato("Documento", cliente?.dniRuc ?: "—")
            FilaDato("Atendido por", vendedor?.name ?: "—")

            Spacer(Modifier.height(14.dp))
            HorizontalDivider(color = coloresMilkFlow.borde)
            Spacer(Modifier.height(14.dp))

            FilaDato("Moldes de queso", "${venta.moldes}")
            FilaDato("Precio unitario", venta.precioUnitario.soles())
            FilaDato(
                "Forma de pago",
                if (venta.esDescuentoLeche) "A cuenta de leche" else "Efectivo",
            )

            Spacer(Modifier.height(10.dp))
            FilaDato("TOTAL", venta.total.soles(), resaltado = true, color = coloresMilkFlow.acento)

            if (venta.pendiente) {
                Spacer(Modifier.height(12.dp))
                Nota(
                    "Este comprobante todavía no llega a la planta. El número definitivo se asigna al sincronizar.",
                    coloresMilkFlow.aviso, "📶",
                )
            }
        }

        if (venta.esDescuentoLeche) {
            Nota(
                "El importe se descontará de la liquidación semanal de leche del proveedor.",
                coloresMilkFlow.info, "ℹ️",
            )
        }
    }
}
