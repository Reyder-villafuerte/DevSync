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
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import com.example.milkflowmovil.core.contieneTexto
import com.example.milkflowmovil.core.litros
import com.example.milkflowmovil.core.soles
import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.datos.local.sobreDe
import com.example.milkflowmovil.dominio.Usuario
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
 * Panel de autorización de pagos.
 *
 * Administración revisa el sobre de cada proveedor antes de que el pagador
 * salga a la ruta del viernes: sin autorización, el efectivo ni siquiera
 * aparece como disponible.
 */
@Composable
fun PantallaAutorizarPagos(repositorio: Repositorio, estado: EstadoLocal) {
    var busqueda by remember { mutableStateOf("") }
    var confirmarTodos by remember { mutableStateOf(false) }

    val productores = estado.productores.filter { it.name.contieneTexto(busqueda) }
    val sobres = productores.map { it to estado.sobreDe(it.id) }
    val porAutorizar = sobres.filterNot { it.second.autorizado }
        .filter { it.second.litros > 0 || it.second.totalDeducciones > 0 }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Por autorizar",
                    "${porAutorizar.size}",
                    "proveedores del ciclo",
                    if (porAutorizar.isNotEmpty()) coloresMilkFlow.aviso else null,
                    Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Neto pendiente",
                    porAutorizar.sumOf { it.second.neto }.soles(),
                    "a pagar en ruta",
                    coloresMilkFlow.acento,
                    Modifier.weight(1f),
                )
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica(
                    "Litros del ciclo",
                    porAutorizar.sumOf { it.second.litros }.litros(),
                    "acumulados",
                    modifier = Modifier.weight(1f),
                )
                TarjetaMetrica(
                    "Deducciones",
                    porAutorizar.sumOf { it.second.totalDeducciones }.soles(),
                    "queso y penalidades",
                    coloresMilkFlow.peligro,
                    Modifier.weight(1f),
                )
            }
        }

        item { Campo(busqueda, "Buscar proveedor", { busqueda = it }) }

        item {
            BotonPrimario(
                "Autorizar todo el ciclo (${porAutorizar.size})",
                Modifier.fillMaxWidth(),
                habilitado = porAutorizar.isNotEmpty(),
            ) { confirmarTodos = true }
        }

        item { EncabezadoSeccion("Sobres del ciclo abierto", "Cálculo con las tarifas y análisis vigentes.") }

        if (sobres.isEmpty()) {
            item { MensajeVacio("No hay proveedores que coincidan.", "💰") }
        }

        items(sobres, key = { it.first.id }) { (productor, sobre) ->
            TarjetaSobre(
                productor = productor,
                sobre = sobre,
                estado = estado,
                accion = if (!sobre.autorizado && (sobre.litros > 0 || sobre.totalDeducciones > 0)) "Autorizar pago" else null,
            ) { repositorio.autorizarPago(productor.id) }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }

    if (confirmarTodos) {
        AlertDialog(
            onDismissRequest = { confirmarTodos = false },
            title = { Text("Autorizar todo el ciclo") },
            text = {
                Text(
                    "Se autorizarán ${porAutorizar.size} sobres por ${porAutorizar.sumOf { it.second.neto }.soles()}. " +
                        "El pagador de campo podrá entregarlos en la ruta del viernes."
                )
            },
            confirmButton = {
                TextButton(onClick = {
                    repositorio.autorizarTodosLosPagos()
                    confirmarTodos = false
                }) { Text("Autorizar") }
            },
            dismissButton = { TextButton(onClick = { confirmarTodos = false }) { Text("Cancelar") } },
        )
    }
}

@Composable
private fun TarjetaSobre(
    productor: Usuario,
    sobre: com.example.milkflowmovil.dominio.Reglas.Sobre,
    estado: EstadoLocal,
    accion: String?,
    alPulsarAccion: () -> Unit,
) {
    var abierto by remember { mutableStateOf(false) }

    Tarjeta(alPulsar = { abierto = !abierto }) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(productor.name, fontWeight = FontWeight.Bold)
                Text(
                    "${estado.zona(productor.zonaId)?.name ?: "Sin zona"} · ${sobre.litros.litros()}",
                    style = MaterialTheme.typography.bodySmall,
                    color = coloresMilkFlow.textoSuave,
                )
            }
            Column(horizontalAlignment = Alignment.End) {
                Text(sobre.neto.soles(), fontWeight = FontWeight.Black, color = coloresMilkFlow.acento)
                if (sobre.autorizado) Etiqueta("Autorizado", coloresMilkFlow.exito)
                else if (sobre.litros <= 0) Etiqueta("Sin entregas", coloresMilkFlow.textoSuave)
            }
        }

        if (abierto) {
            Spacer(Modifier.height(10.dp))
            FilaDato("Ciclo", "${Fechas.corta(sobre.desde)} — ${Fechas.corta(sobre.hasta)}")
            FilaDato("Litros del ciclo", sobre.litros.litros())
            FilaDato("Precio base", sobre.precioBase.soles())
            FilaDato("Bruto", sobre.bruto.soles())

            if (sobre.descuentoQueso > 0) {
                FilaDato("Compras de queso", "- ${sobre.descuentoQueso.soles()}", color = coloresMilkFlow.peligro)
            }

            if (sobre.hayAdulteracion) {
                FilaDato(
                    "Penalidad por agua (${sobre.porcentajeAgua}%)",
                    "- ${sobre.penalidadAgua.soles()}",
                    color = coloresMilkFlow.peligro,
                )
                Spacer(Modifier.height(8.dp))
                Nota(
                    "Regla de Huata: el agua detectada un día descuenta ${sobre.penalidadPorLitro.soles()} por litro " +
                        "sobre TODOS los litros de la semana.",
                    coloresMilkFlow.peligro, "💧",
                )
            }

            Spacer(Modifier.height(6.dp))
            FilaDato("Neto a entregar", sobre.neto.soles(), resaltado = true, color = coloresMilkFlow.acento)

            if (accion != null) {
                Spacer(Modifier.height(12.dp))
                BotonPrimario(accion, Modifier.fillMaxWidth()) { alPulsarAccion() }
            }
        }
    }
}

/**
 * Planilla de sobres del pagador de campo.
 *
 * Un sobre sin autorización de administración no muestra importe ni suma a la
 * custodia de la camioneta: esa es la protección del dinero en ruta.
 */
@Composable
fun PantallaSobresRuta(repositorio: Repositorio, estado: EstadoLocal) {
    var busqueda by remember { mutableStateOf("") }
    var filtro by remember { mutableStateOf("autorizados") }
    var confirmar by remember { mutableStateOf<Usuario?>(null) }

    val hoy = Fechas.hoy()

    val filas = estado.productores
        .filter { it.name.contieneTexto(busqueda) || (it.dni ?: "").contains(busqueda) }
        .map { productor ->
            val liquidacion = estado.liquidaciones
                .filter { it.productorId == productor.id }
                .maxByOrNull { it.id }

            productor to liquidacion
        }
        .filter { (_, liquidacion) ->
            when (filtro) {
                "autorizados" -> liquidacion?.status == "autorizado"
                "pagados" -> liquidacion?.status == "pagado" && liquidacion.pagadoEn?.take(10) == hoy
                else -> true
            }
        }

    val enCustodia = estado.liquidaciones.filter { it.status == "autorizado" }.sumOf { it.neto }
    val entregadoHoy = estado.liquidaciones
        .filter { it.status == "pagado" && it.pagadoEn?.take(10) == hoy }
        .sumOf { it.neto }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("En custodia", enCustodia.soles(), "efectivo por entregar", coloresMilkFlow.acento, Modifier.weight(1f))
                TarjetaMetrica("Entregado hoy", entregadoHoy.soles(), "sobres pagados", coloresMilkFlow.exito, Modifier.weight(1f))
            }
        }

        item { Campo(busqueda, "Buscar proveedor o DNI", { busqueda = it }) }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                listOf("autorizados" to "Por entregar", "pagados" to "Entregados hoy", "todos" to "Todos")
                    .forEach { (clave, texto) -> FilterChip(filtro == clave, { filtro = clave }, { Text(texto) }) }
            }
        }

        if (filas.isEmpty()) {
            item { MensajeVacio("No hay sobres en este filtro.", "💵") }
        }

        items(filas, key = { it.first.id }) { (productor, liquidacion) ->
            val autorizado = liquidacion?.status == "autorizado"
            val pagado = liquidacion?.status == "pagado"

            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(productor.name, fontWeight = FontWeight.Bold)
                        Text(
                            "DNI ${productor.dni ?: "—"} · ${estado.zona(productor.zonaId)?.name ?: "Sin zona"}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text(
                            if (autorizado || pagado) (liquidacion?.neto ?: 0.0).soles() else "— Sin autorizar",
                            fontWeight = FontWeight.Black,
                            color = if (autorizado) coloresMilkFlow.acento else coloresMilkFlow.textoSuave,
                        )
                        if (pagado) Etiqueta("Entregado", coloresMilkFlow.exito)
                    }
                }

                if (liquidacion != null && (autorizado || pagado)) {
                    Spacer(Modifier.height(10.dp))
                    FilaDato("Litros", liquidacion.litros.litros())
                    FilaDato("Bruto", liquidacion.bruto.soles())
                    FilaDato("Deducciones", "- ${liquidacion.deducciones.soles()}", color = coloresMilkFlow.peligro)
                    FilaDato("Neto del sobre", liquidacion.neto.soles(), resaltado = true)
                }

                Spacer(Modifier.height(12.dp))

                when {
                    autorizado -> BotonPrimario("Entregar sobre en efectivo", Modifier.fillMaxWidth()) {
                        confirmar = productor
                    }

                    pagado -> Nota(
                        "Sobre entregado el ${Fechas.corta(liquidacion?.pagadoEn)}.",
                        coloresMilkFlow.exito, "✅",
                    )

                    else -> Nota(
                        "Administración todavía no autoriza este pago. El efectivo no figura en la custodia.",
                        coloresMilkFlow.aviso, "🔒",
                    )
                }
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }

    confirmar?.let { productor ->
        val liquidacion = estado.liquidaciones.firstOrNull { it.productorId == productor.id && it.status == "autorizado" }

        AlertDialog(
            onDismissRequest = { confirmar = null },
            title = { Text("Entregar sobre") },
            text = {
                Text(
                    "Confirmas la entrega de ${(liquidacion?.neto ?: 0.0).soles()} en efectivo a ${productor.name}. " +
                        "Queda registrado con tu nombre y la hora."
                )
            },
            confirmButton = {
                TextButton(onClick = {
                    repositorio.entregarSobre(productor.id)
                    confirmar = null
                }) { Text("Sí, entregué el sobre") }
            },
            dismissButton = { TextButton(onClick = { confirmar = null }) { Text("Cancelar") } },
        )
    }
}

/** Sobres ya entregados, para rendir cuentas al volver de la ruta. */
@Composable
fun PantallaHistorialSobres(estado: EstadoLocal) {
    var busqueda by remember { mutableStateOf("") }

    val pagadas = estado.liquidaciones
        .filter { it.status == "pagado" }
        .filter { (estado.usuario(it.productorId)?.name ?: "").contieneTexto(busqueda) }
        .sortedByDescending { it.pagadoEn ?: "" }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaMetrica("Sobres entregados", "${pagadas.size}", "en total", modifier = Modifier.weight(1f))
                TarjetaMetrica(
                    "Efectivo entregado",
                    pagadas.sumOf { it.neto }.soles(),
                    "histórico",
                    coloresMilkFlow.exito,
                    Modifier.weight(1f),
                )
            }
        }

        item { Campo(busqueda, "Buscar proveedor", { busqueda = it }) }

        if (pagadas.isEmpty()) {
            item { MensajeVacio("Todavía no se entregaron sobres.", "📚") }
        }

        items(pagadas, key = { it.id }) { liquidacion ->
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(
                            estado.usuario(liquidacion.productorId)?.name ?: "Proveedor",
                            fontWeight = FontWeight.Bold,
                        )
                        Text(
                            "${liquidacion.codigo} · ${Fechas.corta(liquidacion.pagadoEn)}",
                            style = MaterialTheme.typography.bodySmall,
                            color = coloresMilkFlow.textoSuave,
                        )
                    }
                    Text(liquidacion.neto.soles(), fontWeight = FontWeight.Black)
                }

                Spacer(Modifier.height(8.dp))
                FilaDato("Periodo", "${Fechas.corta(liquidacion.desde)} — ${Fechas.corta(liquidacion.hasta)}")
                FilaDato("Litros", liquidacion.litros.litros())
                FilaDato("Entregado por", estado.usuario(liquidacion.pagadoPor)?.name ?: "—")
            }
        }

        item { Spacer(Modifier.height(24.dp)) }
    }
}
