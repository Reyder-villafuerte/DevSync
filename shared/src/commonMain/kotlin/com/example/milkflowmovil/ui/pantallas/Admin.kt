package com.example.milkflowmovil.ui.pantallas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.FilterChip
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
import androidx.compose.ui.unit.dp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.litros
import com.example.milkflowmovil.core.soles
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.ui.componentes.BotonPrimario
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
 * Tarifas de temporada.
 *
 * Los precios se historizan: al guardar se abre una tarifa nueva y la anterior
 * queda archivada, para que un recibo viejo siga explicando su propio precio.
 */
@Composable
fun PantallaTarifas(repositorio: Repositorio, estado: EstadoLocal) {
    val vigente = estado.tarifaVigente

    var temporada by remember { mutableStateOf(vigente.temporada) }
    var lecheBase by remember { mutableStateOf(vigente.lecheBase.toString()) }
    var aguaLeve by remember { mutableStateOf(vigente.lecheAguaLeve.toString()) }
    var aguaGrave by remember { mutableStateOf(vigente.lecheAguaGrave.toString()) }
    var quesoProveedor by remember { mutableStateOf(vigente.quesoProveedor.toString()) }
    var quesoMayorista by remember { mutableStateOf(vigente.quesoMayorista.toString()) }
    var quesoLocal by remember { mutableStateOf(vigente.quesoLocal.toString()) }
    var notas by remember { mutableStateOf("") }
    var guardado by remember { mutableStateOf(false) }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Leche base", vigente.lecheBase.soles(), "por litro", coloresMilkFlow.acento, Modifier.weight(1f))
                TarjetaMetrica("Queso local", vigente.quesoLocal.soles(), "por molde", modifier = Modifier.weight(1f))
            }
        }

        item {
            Tarjeta {
                Text("Tarifa vigente: ${vigente.temporada}", style = MaterialTheme.typography.titleMedium)
                Spacer(Modifier.height(10.dp))
                FilaDato("Leche normal", vigente.lecheBase.soles())
                FilaDato("Leche con agua ≤ 5%", vigente.lecheAguaLeve.soles(), color = coloresMilkFlow.aviso)
                FilaDato("Leche con agua > 5%", vigente.lecheAguaGrave.soles(), color = coloresMilkFlow.peligro)
                FilaDato("Queso a proveedor", vigente.quesoProveedor.soles())
                FilaDato("Queso mayorista / 10+", vigente.quesoMayorista.soles())
                FilaDato("Queso local", vigente.quesoLocal.soles())
            }
        }

        item {
            Tarjeta {
                Text("Nueva temporada de precios", style = MaterialTheme.typography.titleMedium)
                Spacer(Modifier.height(10.dp))

                Campo(temporada, "Nombre de la temporada", { temporada = it; guardado = false })
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Campo(lecheBase, "Leche S/ /L", { lecheBase = it }, Modifier.weight(1f), decimal = true)
                    Campo(aguaLeve, "Agua ≤5%", { aguaLeve = it }, Modifier.weight(1f), decimal = true)
                }
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Campo(aguaGrave, "Agua >5%", { aguaGrave = it }, Modifier.weight(1f), decimal = true)
                    Campo(quesoProveedor, "Queso proveedor", { quesoProveedor = it }, Modifier.weight(1f), decimal = true)
                }
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Campo(quesoMayorista, "Queso mayorista", { quesoMayorista = it }, Modifier.weight(1f), decimal = true)
                    Campo(quesoLocal, "Queso local", { quesoLocal = it }, Modifier.weight(1f), decimal = true)
                }
                Spacer(Modifier.height(8.dp))
                Campo(notas, "Motivo del ajuste", { notas = it }, lineas = 2)

                if (guardado) {
                    Spacer(Modifier.height(10.dp))
                    Nota("Tarifas actualizadas. Rigen desde ahora para ventas y liquidaciones.", coloresMilkFlow.exito, "✅")
                }

                Spacer(Modifier.height(12.dp))
                BotonPrimario("Guardar tarifas", Modifier.fillMaxWidth()) {
                    repositorio.actualizarTarifas(
                        temporada = temporada,
                        lecheBase = lecheBase.toDoubleOrNull() ?: vigente.lecheBase,
                        aguaLeve = aguaLeve.toDoubleOrNull() ?: vigente.lecheAguaLeve,
                        aguaGrave = aguaGrave.toDoubleOrNull() ?: vigente.lecheAguaGrave,
                        quesoProveedor = quesoProveedor.toDoubleOrNull() ?: vigente.quesoProveedor,
                        quesoMayorista = quesoMayorista.toDoubleOrNull() ?: vigente.quesoMayorista,
                        quesoLocal = quesoLocal.toDoubleOrNull() ?: vigente.quesoLocal,
                        notas = notas.ifBlank { null },
                    )
                    guardado = true
                }
            }
        }

        item { EncabezadoSeccion("Historial de tarifas") }

        items(estado.tarifas.sortedByDescending { it.id }, key = { it.id }) { tarifa ->
            Tarifa(tarifa)
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

@Composable
private fun Tarifa(tarifa: com.example.milkflowmovil.dominio.Tarifa) {
    Tarjeta {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(tarifa.temporada, fontWeight = FontWeight.Bold)
                Text(
                    "Leche ${tarifa.lecheBase.soles()} · Queso ${tarifa.quesoLocal.soles()}",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )
            }
            if (tarifa.activa) Etiqueta("Vigente", coloresMilkFlow.exito)
        }
    }
}

/** Avisos que se muestran al iniciar sesión, por rol o para todos. */
@Composable
fun PantallaAvisos(repositorio: Repositorio, estado: EstadoLocal) {
    var titulo by remember { mutableStateOf("") }
    var mensaje by remember { mutableStateOf("") }
    var desde by remember { mutableStateOf(Fechas.hoy()) }
    var hasta by remember { mutableStateOf(Fechas.sumarDias(Fechas.hoy(), 7)) }
    var destino by remember { mutableStateOf<String?>(null) }
    var publicado by remember { mutableStateOf(false) }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Tarjeta {
                Text("Publicar aviso", style = MaterialTheme.typography.titleMedium)
                Spacer(Modifier.height(10.dp))

                Campo(titulo, "Título", { titulo = it; publicado = false })
                Spacer(Modifier.height(8.dp))
                Campo(mensaje, "Mensaje", { mensaje = it }, lineas = 3)
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Campo(desde, "Desde (aaaa-mm-dd)", { desde = it }, Modifier.weight(1f))
                    Campo(hasta, "Hasta (aaaa-mm-dd)", { hasta = it }, Modifier.weight(1f))
                }

                Spacer(Modifier.height(10.dp))
                Text("Destinatarios", style = MaterialTheme.typography.labelLarge)
                Spacer(Modifier.height(6.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    FilterChip(destino == null, { destino = null }, { Text("Todos") })
                    FilterChip(destino == "productor", { destino = "productor" }, { Text("Productores") })
                    FilterChip(destino == "acopiador", { destino = "acopiador" }, { Text("Acopiadores") })
                }

                if (publicado) {
                    Spacer(Modifier.height(10.dp))
                    Nota("Aviso publicado. Aparecerá al iniciar sesión dentro del rango de fechas.", coloresMilkFlow.exito, "✅")
                }

                Spacer(Modifier.height(12.dp))
                BotonPrimario(
                    "Publicar aviso",
                    Modifier.fillMaxWidth(),
                    habilitado = titulo.isNotBlank() && mensaje.isNotBlank(),
                ) {
                    repositorio.publicarAviso(titulo, mensaje, desde, hasta, destino)
                    titulo = ""
                    mensaje = ""
                    publicado = true
                }
            }
        }

        item { EncabezadoSeccion("Avisos publicados") }

        val avisos = estado.avisos.sortedByDescending { it.id }

        if (avisos.isEmpty()) {
            item { MensajeVacio("No hay avisos publicados.", "📢") }
        }

        items(avisos, key = { it.id }) { aviso ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(aviso.title, fontWeight = FontWeight.Bold)
                        Text(
                            "${Fechas.corta(aviso.desde)} — ${Fechas.corta(aviso.hasta)}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Etiqueta(
                        aviso.rolDestino?.replaceFirstChar { it.uppercase() } ?: "Todos",
                        coloresMilkFlow.info,
                    )
                }
                Spacer(Modifier.height(8.dp))
                Text(aviso.message, style = MaterialTheme.typography.bodySmall)

                if (aviso.pendiente) {
                    Spacer(Modifier.height(8.dp))
                    Etiqueta("Sin subir", coloresMilkFlow.aviso)
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}

/** Flujo de caja: ingresos por venta de queso contra egresos de la asociación. */
@Composable
fun PantallaFinanzas(repositorio: Repositorio, estado: EstadoLocal) {
    var periodo by remember { mutableStateOf("mes") }
    var mostrarFormulario by remember { mutableStateOf(false) }

    var categoria by remember { mutableStateOf("pago_personal") }
    var descripcion by remember { mutableStateOf("") }
    var monto by remember { mutableStateOf("") }
    var beneficiario by remember { mutableStateOf("") }

    val desde = when (periodo) {
        "hoy" -> Fechas.hoy()
        "semana" -> Fechas.inicioSemana()
        else -> Fechas.hoy().take(7) + "-01"
    }

    val ventas = estado.ventas.filter { it.vendidoEn.take(10) >= desde }
    val ingresoEfectivo = ventas.filterNot { it.esDescuentoLeche }.sumOf { it.total }
    val ingresoLeche = ventas.filter { it.esDescuentoLeche }.sumOf { it.total }

    val liquidaciones = estado.liquidaciones.filter {
        it.status in listOf("autorizado", "pagado") && (it.pagadoEn?.take(10) ?: it.hasta) >= desde
    }
    val egresoProveedores = liquidaciones.sumOf { it.neto }

    val egresos = estado.egresos.filter { it.fecha >= desde }
    val egresoPersonal = egresos.filter { it.category == "pago_personal" }.sumOf { it.amount }
    val egresoOperativo = egresos.filterNot { it.category == "pago_personal" }.sumOf { it.amount }

    val totalIngresos = ingresoEfectivo + ingresoLeche
    val totalEgresos = egresoProveedores + egresoPersonal + egresoOperativo
    val balance = totalIngresos - totalEgresos

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                listOf("hoy" to "Hoy", "semana" to "Semana", "mes" to "Mes").forEach { (clave, texto) ->
                    FilterChip(periodo == clave, { periodo = clave }, { Text(texto) })
                }
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Ingresos", totalIngresos.soles(), "venta de queso", coloresMilkFlow.exito, Modifier.weight(1f))
                TarjetaMetrica("Egresos", totalEgresos.soles(), "leche, planilla y operación", coloresMilkFlow.peligro, Modifier.weight(1f))
            }
        }

        item {
            TarjetaMetrica(
                "Balance neto operativo",
                balance.soles(),
                "desde ${Fechas.corta(desde)}",
                if (balance >= 0) coloresMilkFlow.exito else coloresMilkFlow.peligro,
                Modifier.fillMaxWidth(),
            )
        }

        item {
            Tarjeta {
                Text("Detalle del periodo", style = MaterialTheme.typography.titleMedium)
                Spacer(Modifier.height(10.dp))
                FilaDato("Ventas en efectivo", ingresoEfectivo.soles(), color = coloresMilkFlow.exito)
                FilaDato("Ventas a cuenta de leche", ingresoLeche.soles(), color = coloresMilkFlow.aviso)
                FilaDato("Moldes vendidos", "${ventas.sumOf { it.moldes }}")
                Spacer(Modifier.height(8.dp))
                FilaDato("Liquidaciones a proveedores", "- ${egresoProveedores.soles()}", color = coloresMilkFlow.peligro)
                FilaDato("Litros liquidados", liquidaciones.sumOf { it.litros }.litros())
                FilaDato("Pago de personal", "- ${egresoPersonal.soles()}", color = coloresMilkFlow.peligro)
                FilaDato("Gastos operativos", "- ${egresoOperativo.soles()}", color = coloresMilkFlow.peligro)
            }
        }

        item {
            BotonPrimario(
                if (mostrarFormulario) "Ocultar formulario" else "+ Registrar egreso",
                Modifier.fillMaxWidth(),
            ) { mostrarFormulario = !mostrarFormulario }
        }

        if (mostrarFormulario) {
            item {
                Tarjeta {
                    Text("Nuevo egreso", style = MaterialTheme.typography.titleMedium)
                    Spacer(Modifier.height(10.dp))

                    Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                        listOf(
                            "pago_personal" to "Personal",
                            "combustible_ruta" to "Combustible",
                            "insumos_planta" to "Insumos",
                        ).forEach { (clave, texto) ->
                            FilterChip(categoria == clave, { categoria = clave }, { Text(texto) })
                        }
                    }

                    Spacer(Modifier.height(10.dp))
                    Campo(descripcion, "Descripción", { descripcion = it })
                    Spacer(Modifier.height(8.dp))
                    Campo(monto, "Monto S/", { monto = it }, decimal = true)
                    Spacer(Modifier.height(8.dp))
                    Campo(beneficiario, "Beneficiario", { beneficiario = it })

                    Spacer(Modifier.height(12.dp))
                    BotonPrimario(
                        "Registrar egreso",
                        Modifier.fillMaxWidth(),
                        habilitado = descripcion.isNotBlank() && (monto.toDoubleOrNull() ?: 0.0) > 0,
                    ) {
                        repositorio.registrarEgreso(
                            categoria = categoria,
                            descripcion = descripcion,
                            monto = monto.toDoubleOrNull() ?: 0.0,
                            fecha = Fechas.hoy(),
                            beneficiario = beneficiario.ifBlank { null },
                            personalId = null,
                            formaPago = "efectivo",
                            comprobante = null,
                        )
                        descripcion = ""
                        monto = ""
                        beneficiario = ""
                        mostrarFormulario = false
                    }
                }
            }
        }

        item { EncabezadoSeccion("Egresos del periodo") }

        if (egresos.isEmpty()) {
            item { MensajeVacio("No hay egresos registrados en el periodo.", "📊") }
        }

        items(egresos.sortedByDescending { it.fecha }, key = { it.id }) { egreso ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(egreso.description, fontWeight = FontWeight.Bold)
                        Text(
                            "${Fechas.corta(egreso.fecha)} · ${egreso.beneficiario ?: "—"}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Text("- ${egreso.amount.soles()}", fontWeight = FontWeight.Black, color = coloresMilkFlow.peligro)
                }
                if (egreso.pendiente) {
                    Spacer(Modifier.height(8.dp))
                    Etiqueta("Sin subir", coloresMilkFlow.aviso)
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}
