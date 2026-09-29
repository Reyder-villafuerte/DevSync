package com.example.milkflowmovil.datos

import com.example.milkflowmovil.core.ErrorApp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.datos.local.BaseLocal
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.datos.local.OperacionPendiente
import com.example.milkflowmovil.datos.local.OperacionRechazada
import com.example.milkflowmovil.datos.remoto.ApiMilkFlow
import com.example.milkflowmovil.datos.remoto.OperacionSubida
import com.example.milkflowmovil.datos.remoto.ResultadoOperacion
import com.example.milkflowmovil.dominio.Analisis
import com.example.milkflowmovil.dominio.Aviso
import com.example.milkflowmovil.dominio.CierreCaja
import com.example.milkflowmovil.dominio.Cliente
import com.example.milkflowmovil.dominio.Descuento
import com.example.milkflowmovil.dominio.Egreso
import com.example.milkflowmovil.dominio.Entrega
import com.example.milkflowmovil.dominio.Liquidacion
import com.example.milkflowmovil.dominio.Recepcion
import com.example.milkflowmovil.dominio.Ruta
import com.example.milkflowmovil.dominio.SolicitudZona
import com.example.milkflowmovil.dominio.Stock
import com.example.milkflowmovil.dominio.Tarifa
import com.example.milkflowmovil.dominio.Usuario
import com.example.milkflowmovil.dominio.Venta
import com.example.milkflowmovil.dominio.VisitaTecnica
import com.example.milkflowmovil.dominio.Zona
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.sync.Mutex
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.decodeFromJsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.long

/** Lo que la barra superior muestra sobre el estado de la sincronización. */
data class EstadoSync(
    val sincronizando: Boolean = false,
    val pendientes: Int = 0,
    val ultimaSincronizacion: String? = null,
    val ultimoError: String? = null,
)

/**
 * Motor de sincronización del teléfono.
 *
 * Siempre en el mismo orden: primero sube lo que se hizo sin señal, después
 * baja los cambios del servidor. Así la bajada nunca pisa trabajo que todavía
 * no llegó a subir.
 */
class Sincronizador(
    private val base: BaseLocal,
    private val api: ApiMilkFlow,
) {
    private val json = Json { ignoreUnknownKeys = true; isLenient = true; explicitNulls = false }
    private val candado = Mutex()

    private val _estado = MutableStateFlow(EstadoSync())
    val estado: StateFlow<EstadoSync> = _estado.asStateFlow()

    /**
     * Un ciclo completo. Si no hay señal no es un error: la cola se queda
     * esperando y se avisa en la barra superior.
     */
    suspend fun sincronizar(): Resultado<Unit> {
        val sesion = base.actual.sesion ?: return Resultado.Fallo(ErrorApp.SesionVencida)

        if (!candado.tryLock()) return Resultado.Exito(Unit)

        _estado.value = _estado.value.copy(sincronizando = true, ultimoError = null)

        try {
            val subida = subirCola(sesion.token, sesion.dispositivoId)
            if (subida is Resultado.Fallo) {
                return registrarFallo(subida.error)
            }

            val bajada = bajarCambios(sesion.token)
            if (bajada is Resultado.Fallo) {
                return registrarFallo(bajada.error)
            }

            val ahora = Fechas.ahoraIso()
            base.actualizar { it.copy(ultimaSincronizacion = ahora, ultimoErrorSync = null) }

            _estado.value = EstadoSync(
                sincronizando = false,
                pendientes = base.actual.cola.size,
                ultimaSincronizacion = ahora,
            )

            return Resultado.Exito(Unit)
        } finally {
            if (candado.isLocked) candado.unlock()
            _estado.value = _estado.value.copy(sincronizando = false, pendientes = base.actual.cola.size)
        }
    }

    private fun registrarFallo(error: ErrorApp): Resultado<Unit> {
        base.actualizar { it.copy(ultimoErrorSync = error.mensaje) }
        _estado.value = _estado.value.copy(
            sincronizando = false,
            pendientes = base.actual.cola.size,
            ultimoError = error.mensaje,
        )
        return Resultado.Fallo(error)
    }

    // ---------------------------------------------------------------- SUBIDA

    private suspend fun subirCola(token: String, dispositivoId: String): Resultado<Unit> {
        val cola = base.actual.cola
        if (cola.isEmpty()) return Resultado.Exito(Unit)

        val lote = cola.take(TOPE_LOTE).map {
            OperacionSubida(it.clientUuid, it.comando, it.payload)
        }

        return when (val respuesta = api.push(token, dispositivoId, lote)) {
            is Resultado.Fallo -> Resultado.Fallo(respuesta.error)
            is Resultado.Exito -> {
                respuesta.valor.resultados.forEach { aplicarResultado(it) }
                Resultado.Exito(Unit)
            }
        }
    }

    private fun aplicarResultado(resultado: ResultadoOperacion) {
        base.actualizar { estado ->
            val operacion = estado.cola.firstOrNull { it.clientUuid == resultado.clientUuid }

            when {
                resultado.aplicada -> confirmar(estado, resultado)
                    .copy(cola = estado.cola.filterNot { it.clientUuid == resultado.clientUuid })

                resultado.rechazada -> descartar(estado, resultado.clientUuid).copy(
                    cola = estado.cola.filterNot { it.clientUuid == resultado.clientUuid },
                    rechazadas = estado.rechazadas + OperacionRechazada(
                        clientUuid = resultado.clientUuid,
                        comando = resultado.comando,
                        descripcion = operacion?.descripcion ?: resultado.comando,
                        motivo = resultado.mensaje ?: "El servidor rechazó la operación.",
                        rechazadaEn = Fechas.ahoraIso(),
                    ),
                )

                // Error técnico: se queda en la cola para el próximo intento.
                else -> estado.copy(
                    cola = estado.cola.map {
                        if (it.clientUuid == resultado.clientUuid) {
                            it.copy(intentos = it.intentos + 1, ultimoError = resultado.mensaje)
                        } else {
                            it
                        }
                    }
                )
            }
        }
    }

    /**
     * La operación se aplicó: la fila local provisional adopta el id real del
     * servidor y deja de estar pendiente.
     */
    private fun confirmar(estado: EstadoLocal, resultado: ResultadoOperacion): EstadoLocal {
        val datos = resultado.datos ?: return estado
        val entidad = datos["entidad"]?.jsonPrimitive?.contentOrNulo() ?: return estado
        val idServidor = datos["id"]?.jsonPrimitive?.longOrNulo()
        val uuid = resultado.clientUuid

        return when (entidad) {
            "collection_records" -> {
                val rutaId = datos["ruta_id"]?.jsonPrimitive?.longOrNulo()
                val uuidRuta = datos["ruta_client_uuid"]?.jsonPrimitive?.contentOrNulo()

                estado.copy(
                    entregas = estado.entregas.map { entrega ->
                        if (entrega.clientUuid != uuid) entrega
                        else entrega.copy(
                            id = idServidor ?: entrega.id,
                            rutaId = rutaId ?: entrega.rutaId,
                            pendiente = false,
                        )
                    },
                    // La ruta creada sin señal también recibe su id definitivo.
                    rutas = estado.rutas.map { ruta ->
                        if (uuidRuta != null && ruta.clientUuid == uuidRuta && rutaId != null) {
                            ruta.copy(id = rutaId, pendiente = false)
                        } else {
                            ruta
                        }
                    },
                )
            }

            "collection_routes" -> estado.copy(
                rutas = estado.rutas.map {
                    if (it.clientUuid == uuid && idServidor != null) it.copy(id = idServidor, pendiente = false)
                    else if (it.clientUuid == uuid) it.copy(pendiente = false) else it
                }
            )

            "plant_receptions" -> estado.copy(
                recepciones = estado.recepciones.map {
                    if (it.clientUuid == uuid) it.copy(id = idServidor ?: it.id, pendiente = false) else it
                }
            )

            "sales" -> estado.copy(
                ventas = estado.ventas.map { venta ->
                    if (venta.clientUuid != uuid) venta
                    else venta.copy(
                        id = idServidor ?: venta.id,
                        recibo = datos["receipt_number"]?.jsonPrimitive?.contentOrNulo() ?: venta.recibo,
                        precioUnitario = datos["unit_price"]?.jsonPrimitive?.doubleOrNulo() ?: venta.precioUnitario,
                        total = datos["total_amount"]?.jsonPrimitive?.doubleOrNulo() ?: venta.total,
                        pendiente = false,
                    )
                }
            )

            "daily_cash_closures" -> estado.copy(
                cierresCaja = estado.cierresCaja.map {
                    if (it.clientUuid == uuid) it.copy(id = idServidor ?: it.id, pendiente = false) else it
                }
            )

            "lactoscan_analyses" -> estado.copy(
                analisis = estado.analisis.map {
                    if (it.clientUuid == uuid) it.copy(id = idServidor ?: it.id, pendiente = false) else it
                }
            )

            "technical_visits" -> estado.copy(
                visitas = estado.visitas.map {
                    if (it.clientUuid == uuid) it.copy(id = idServidor ?: it.id, pendiente = false) else it
                }
            )

            "zone_change_requests" -> estado.copy(
                solicitudesZona = estado.solicitudesZona.map {
                    if (it.clientUuid == uuid) it.copy(id = idServidor ?: it.id, pendiente = false) else it
                }
            )

            "operational_expenses" -> estado.copy(
                egresos = estado.egresos.map {
                    if (it.clientUuid == uuid) it.copy(id = idServidor ?: it.id, pendiente = false) else it
                }
            )

            "system_prices" -> estado.copy(
                tarifas = estado.tarifas.map {
                    if (it.id < 0 && idServidor != null) it.copy(id = idServidor) else it
                }
            )

            "announcements" -> estado.copy(
                avisos = estado.avisos.map {
                    if (it.clientUuid == uuid) it.copy(id = idServidor ?: it.id, pendiente = false) else it
                }
            )

            // Liquidaciones y sobres los recalcula el servidor: llegan en la bajada.
            else -> estado
        }
    }

    /** La operación fue rechazada: se deshace el cambio optimista del teléfono. */
    private fun descartar(estado: EstadoLocal, uuid: String): EstadoLocal = estado.copy(
        entregas = estado.entregas.filterNot { it.clientUuid == uuid && it.pendiente },
        rutas = estado.rutas.filterNot { it.clientUuid == uuid && it.pendiente },
        recepciones = estado.recepciones.filterNot { it.clientUuid == uuid && it.pendiente },
        ventas = estado.ventas.filterNot { it.clientUuid == uuid && it.pendiente },
        cierresCaja = estado.cierresCaja.filterNot { it.clientUuid == uuid && it.pendiente },
        analisis = estado.analisis.filterNot { it.clientUuid == uuid && it.pendiente },
        visitas = estado.visitas.filterNot { it.clientUuid == uuid && it.pendiente },
        solicitudesZona = estado.solicitudesZona.filterNot { it.clientUuid == uuid && it.pendiente },
        egresos = estado.egresos.filterNot { it.clientUuid == uuid && it.pendiente },
        avisos = estado.avisos.filterNot { it.clientUuid == uuid && it.pendiente },
    )

    // ---------------------------------------------------------------- BAJADA

    private suspend fun bajarCambios(token: String): Resultado<Unit> {
        val cursores = base.actual.cursores

        return when (val respuesta = api.pull(token, cursores)) {
            is Resultado.Fallo -> Resultado.Fallo(respuesta.error)
            is Resultado.Exito -> {
                base.actualizar { estado ->
                    var nuevo = estado
                    val nuevosCursores = estado.cursores.toMutableMap()

                    respuesta.valor.entidades.forEach { (entidad, bloque) ->
                        nuevo = fusionarEntidad(nuevo, entidad, bloque.filas)
                        bloque.cursor?.let { nuevosCursores[entidad] = it }
                    }

                    nuevo.copy(cursores = nuevosCursores)
                }
                Resultado.Exito(Unit)
            }
        }
    }

    private fun fusionarEntidad(estado: EstadoLocal, entidad: String, filas: List<JsonObject>): EstadoLocal {
        if (filas.isEmpty()) return estado

        return when (entidad) {
            "zones" -> estado.copy(zonas = fusionar(estado.zonas, decodificar<Zona>(filas)) { it.id })
            "users" -> estado.copy(usuarios = fusionar(estado.usuarios, decodificar<Usuario>(filas)) { it.id })
            "system_prices" -> estado.copy(tarifas = fusionar(estado.tarifas, decodificar<Tarifa>(filas)) { it.id })
            // El stock se identifica por su código: la fila del servidor sustituye
            // a la provisional que el teléfono creó al ajustar sin señal.
            "inventory_stocks" -> {
                val nuevos = decodificar<Stock>(filas)
                val codigos = nuevos.map { it.codigo }.toSet()
                estado.copy(stocks = estado.stocks.filterNot { codigos.contains(it.codigo) } + nuevos)
            }
            "collection_routes" -> estado.copy(rutas = fusionar(estado.rutas, decodificar<Ruta>(filas)) { it.id })
            "collection_records" -> estado.copy(entregas = fusionar(estado.entregas, decodificar<Entrega>(filas)) { it.id })
            "plant_receptions" -> estado.copy(recepciones = fusionar(estado.recepciones, decodificar<Recepcion>(filas)) { it.id })
            "customers" -> estado.copy(clientes = fusionar(estado.clientes, decodificar<Cliente>(filas)) { it.id })
            "sales" -> estado.copy(ventas = fusionar(estado.ventas, decodificar<Venta>(filas)) { it.id })
            "daily_cash_closures" -> estado.copy(cierresCaja = fusionar(estado.cierresCaja, decodificar<CierreCaja>(filas)) { it.id })
            "lactoscan_analyses" -> estado.copy(analisis = fusionar(estado.analisis, decodificar<Analisis>(filas)) { it.id })
            "technical_visits" -> estado.copy(visitas = fusionar(estado.visitas, decodificar<VisitaTecnica>(filas)) { it.id })
            "zone_change_requests" -> estado.copy(solicitudesZona = fusionar(estado.solicitudesZona, decodificar<SolicitudZona>(filas)) { it.id })
            "producer_settlements" -> estado.copy(liquidaciones = fusionar(estado.liquidaciones, decodificar<Liquidacion>(filas)) { it.id })
            "producer_deductions" -> estado.copy(descuentos = fusionar(estado.descuentos, decodificar<Descuento>(filas)) { it.id })
            "operational_expenses" -> estado.copy(egresos = fusionar(estado.egresos, decodificar<Egreso>(filas)) { it.id })
            "announcements" -> estado.copy(avisos = fusionar(estado.avisos, decodificar<Aviso>(filas)) { it.id })
            else -> estado
        }
    }

    private inline fun <reified T> decodificar(filas: List<JsonObject>): List<T> =
        filas.mapNotNull { fila -> runCatching { json.decodeFromJsonElement<T>(fila) }.getOrNull() }

    /**
     * La fila del servidor manda sobre la copia local ya confirmada; las filas
     * pendientes de subir (id negativo) se conservan intactas.
     */
    private fun <T> fusionar(actuales: List<T>, nuevos: List<T>, id: (T) -> Long): List<T> {
        if (nuevos.isEmpty()) return actuales

        val idsNuevos = nuevos.map(id).toSet()
        val conservadas = actuales.filterNot { idsNuevos.contains(id(it)) }

        return (conservadas + nuevos).sortedBy { id(it) }
    }

    private companion object {
        const val TOPE_LOTE = 50
    }
}

private fun kotlinx.serialization.json.JsonPrimitive.contentOrNulo(): String? =
    content.takeIf { it.isNotBlank() && it != "null" }

private fun kotlinx.serialization.json.JsonPrimitive.longOrNulo(): Long? =
    runCatching { long }.getOrNull() ?: content.toDoubleOrNull()?.toLong()

private fun kotlinx.serialization.json.JsonPrimitive.doubleOrNulo(): Double? = content.toDoubleOrNull()
