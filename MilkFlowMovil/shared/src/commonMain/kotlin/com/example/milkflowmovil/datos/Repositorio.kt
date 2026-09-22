package com.example.milkflowmovil.datos

import com.example.milkflowmovil.core.ErrorApp
import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.datos.local.BaseLocal
import com.example.milkflowmovil.datos.local.EstadoLocal
import com.example.milkflowmovil.datos.local.OperacionPendiente
import com.example.milkflowmovil.datos.local.Sesion
import com.example.milkflowmovil.datos.local.identificadorDispositivo
import com.example.milkflowmovil.datos.local.nuevoUuid
import com.example.milkflowmovil.datos.remoto.ApiMilkFlow
import com.example.milkflowmovil.dominio.Analisis
import com.example.milkflowmovil.dominio.Aviso
import com.example.milkflowmovil.dominio.CierreCaja
import com.example.milkflowmovil.dominio.Egreso
import com.example.milkflowmovil.dominio.Entrega
import com.example.milkflowmovil.dominio.Recepcion
import com.example.milkflowmovil.dominio.Reglas
import com.example.milkflowmovil.dominio.Ruta
import com.example.milkflowmovil.dominio.SolicitudZona
import com.example.milkflowmovil.dominio.Venta
import com.example.milkflowmovil.dominio.VisitaTecnica
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/**
 * Fachada que usa toda la interfaz.
 *
 * Regla del proyecto: ninguna pantalla habla con la red. Las acciones escriben
 * de inmediato en la base local (para que el usuario vea el resultado al
 * instante, con o sin señal) y encolan la operación para el servidor.
 */
class Repositorio(
    private val base: BaseLocal,
    private val api: ApiMilkFlow,
    private val sincronizador: Sincronizador,
    private val alcance: CoroutineScope,
) {
    val estado: StateFlow<EstadoLocal> = base.estado
    val estadoSync: StateFlow<EstadoSync> = sincronizador.estado

    private val dispositivoId = identificadorDispositivo()

    val actual: EstadoLocal get() = base.actual
    val sesion: Sesion? get() = base.actual.sesion
    val yo get() = base.actual.sesion?.usuario

    suspend fun iniciar() {
        base.cargar()
        if (base.actual.sesion != null) {
            sincronizar()
        }
        arrancarCicloAutomatico()
    }

    // ----------------------------------------------------------- SESIÓN

    suspend fun iniciarSesion(usuario: String, password: String): Resultado<Unit> {
        return when (val respuesta = api.login(usuario.trim(), password, dispositivoId)) {
            is Resultado.Fallo -> Resultado.Fallo(respuesta.error)
            is Resultado.Exito -> {
                val datos = respuesta.valor
                val urlActual = base.actual.urlBase
                val eraOtroUsuario = base.actual.sesion?.usuario?.id != datos.usuario.id

                // La base local es de una sola persona: si entra otra, se limpia.
                if (eraOtroUsuario) base.limpiar(urlActual)

                base.actualizar { estado ->
                    estado.copy(
                        sesion = Sesion(
                            token = datos.token,
                            usuario = datos.usuario,
                            dispositivoId = dispositivoId,
                            iniciadaEn = Fechas.ahoraIso(),
                        ),
                        avisos = datos.avisos.ifEmpty { estado.avisos },
                    )
                }

                // La primera bajada se lanza en el ámbito del repositorio: la
                // pantalla de login desaparece en cuanto hay sesión y con ella
                // se cancelaría la petición a media descarga.
                sincronizarEnSegundoPlano()
                Resultado.Exito(Unit)
            }
        }
    }

    suspend fun cerrarSesion() {
        val token = base.actual.sesion?.token
        val url = base.actual.urlBase
        if (token != null) api.cerrarSesion(token)
        base.limpiar(url)
    }

    fun cambiarServidor(url: String) {
        base.actualizar { it.copy(urlBase = url.trim().trimEnd('/')) }
    }

    // --------------------------------------------------- SINCRONIZACIÓN

    suspend fun sincronizar(): Resultado<Unit> = sincronizador.sincronizar()

    fun sincronizarEnSegundoPlano() {
        alcance.launch { sincronizador.sincronizar() }
    }

    /**
     * Reintento automático: cada minuto cuando hay trabajo en cola, cada cinco
     * cuando no lo hay. Sin señal simplemente falla en silencio y espera.
     */
    private fun arrancarCicloAutomatico() {
        alcance.launch {
            while (isActive) {
                val hayTrabajo = base.actual.cola.isNotEmpty() || base.actual.ultimoErrorSync != null
                delay(if (hayTrabajo) 60_000L else 300_000L)

                if (base.actual.sesion != null) {
                    sincronizador.sincronizar()
                }
            }
        }
    }

    private fun encolar(uuid: String, comando: String, payload: JsonObject, descripcion: String) {
        base.actualizar { estado ->
            estado.copy(
                cola = estado.cola + OperacionPendiente(
                    clientUuid = uuid,
                    comando = comando,
                    payload = payload,
                    descripcion = descripcion,
                    creadaEn = Fechas.ahoraIso(),
                )
            )
        }
        sincronizarEnSegundoPlano()
    }

    fun descartarRechazada(uuid: String) {
        base.actualizar { estado ->
            estado.copy(rechazadas = estado.rechazadas.filterNot { it.clientUuid == uuid })
        }
    }

    // ------------------------------------------------------------ ACOPIO

    /** Ruta de hoy del acopiador; si no existe, se abre una provisional local. */
    fun rutaDeHoy(): Ruta? {
        val usuario = yo ?: return null
        return base.actual.rutas.firstOrNull { it.date == Fechas.hoy() && it.acopiadorId == usuario.id }
    }

    fun abrirRutaDeHoy(): Ruta? {
        val usuario = yo ?: return null
        rutaDeHoy()?.let { return it }

        val uuid = nuevoUuid()
        val idTemporal = base.reservarIdTemporal()

        // La zona definitiva la decide el servidor (evita que dos acopiadores
        // tomen la misma zona); aquí se muestra la del padrón como referencia.
        val ruta = Ruta(
            id = idTemporal,
            clientUuid = uuid,
            date = Fechas.hoy(),
            zonaId = usuario.zonaId ?: base.actual.zonas.firstOrNull()?.id ?: 0,
            acopiadorId = usuario.id,
            horaInicio = "04:30:00",
            status = "asignada",
            litrosTotales = 0.0,
            pendiente = true,
        )

        base.actualizar { it.copy(rutas = it.rutas + ruta) }

        encolar(
            uuid, "abrir_ruta",
            buildJsonObject { put("fecha", Fechas.hoy()) },
            "Apertura de ruta del ${Fechas.corta(Fechas.hoy())}",
        )

        return ruta
    }

    fun registrarEntrega(productorId: Long, litros: Double, notas: String?) {
        val ruta = rutaDeHoy() ?: abrirRutaDeHoy() ?: return
        val productor = base.actual.usuario(productorId)
        val uuid = nuevoUuid()
        val hora = Fechas.horaActual()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val previa = estado.entregas.firstOrNull {
                it.rutaId == ruta.id && it.productorId == productorId
            }

            val entrega = (previa ?: Entrega(id = idNuevo, rutaId = ruta.id)).copy(
                clientUuid = uuid,
                productorId = productorId,
                liters = litros,
                hora = hora,
                notes = notas,
                pendiente = true,
                rutaClientUuid = ruta.clientUuid,
            )

            val entregas = estado.entregas.filterNot { it.id == entrega.id } + entrega
            val totalRuta = entregas.filter { it.rutaId == ruta.id }.sumOf { it.liters }

            estado.copy(
                entregas = entregas,
                rutas = estado.rutas.map {
                    if (it.id == ruta.id) {
                        it.copy(
                            litrosTotales = totalRuta,
                            status = if (it.status == "asignada") "en_ruta" else it.status,
                        )
                    } else {
                        it
                    }
                },
            )
        }

        encolar(
            uuid, "registrar_entrega",
            buildJsonObject {
                ruta.clientUuid?.let { put("ruta_client_uuid", it) }
                if (ruta.id > 0) put("ruta_id", ruta.id)
                put("fecha", ruta.date)
                put("producer_id", productorId)
                put("liters", litros)
                put("collected_at", hora)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
            },
            "Entrega de ${litros} L de ${productor?.name ?: "productor"}",
        )
    }

    fun cerrarRuta() {
        val ruta = rutaDeHoy() ?: return
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(rutas = estado.rutas.map {
                if (it.id == ruta.id) it.copy(status = "descargada_planta") else it
            })
        }

        encolar(
            uuid, "cerrar_ruta",
            buildJsonObject {
                ruta.clientUuid?.let { put("ruta_client_uuid", it) }
                if (ruta.id > 0) put("ruta_id", ruta.id)
                put("fecha", ruta.date)
            },
            "Cierre de ruta del ${Fechas.corta(ruta.date)}",
        )
    }

    // ------------------------------------------------------------ PLANTA

    fun verificarRecepcion(ruta: Ruta, litrosCaudalimetro: Double, estadoVerificacion: String, observacion: String?) {
        val usuario = yo ?: return
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val previa = estado.recepciones.firstOrNull { it.rutaId == ruta.id }

            val recepcion = (previa ?: Recepcion(id = idNuevo, rutaId = ruta.id)).copy(
                clientUuid = uuid,
                verificadorId = usuario.id,
                litrosDeclarados = ruta.litrosTotales,
                litrosCaudalimetro = litrosCaudalimetro,
                diferencia = Reglas.merma(ruta.litrosTotales, litrosCaudalimetro),
                estado = estadoVerificacion,
                observation = observacion,
                verificadoEn = Fechas.ahoraIso(),
                pendiente = true,
            )

            val litrosPrevios = previa?.litrosCaudalimetro ?: 0.0

            estado.copy(
                recepciones = estado.recepciones.filterNot { it.id == recepcion.id } + recepcion,
                rutas = estado.rutas.map { if (it.id == ruta.id) it.copy(status = "verificada") else it },
                // Al stock entra solo lo del caudalímetro; si se corrige, el ajuste es el delta.
                stocks = ajustarStock(estado, com.example.milkflowmovil.dominio.Stock.LECHE, litrosCaudalimetro - litrosPrevios),
            )
        }

        encolar(
            uuid, "verificar_recepcion",
            buildJsonObject {
                if (ruta.id > 0) put("ruta_id", ruta.id)
                ruta.clientUuid?.let { put("ruta_client_uuid", it) }
                put("flowmeter_liters", litrosCaudalimetro)
                put("verification_status", estadoVerificacion)
                observacion?.takeIf { it.isNotBlank() }?.let { put("observation", it) }
            },
            "Caudalímetro de la ruta del ${Fechas.corta(ruta.date)}: $litrosCaudalimetro L",
        )
    }

    /**
     * Ajusta el stock local. Si la fila aún no bajó del servidor se crea con un
     * id provisional fijo por código; la bajada la reemplaza por código, no por id.
     */
    private fun ajustarStock(estado: EstadoLocal, codigo: String, delta: Double): List<com.example.milkflowmovil.dominio.Stock> {
        val existente = estado.stocks.firstOrNull { it.codigo == codigo }
            ?: com.example.milkflowmovil.dominio.Stock(
                id = if (codigo == com.example.milkflowmovil.dominio.Stock.LECHE) ID_STOCK_LECHE else ID_STOCK_QUESO,
                codigo = codigo,
                nombre = if (codigo == com.example.milkflowmovil.dominio.Stock.LECHE) "Leche fresca" else "Moldes de queso",
                unit = if (codigo == com.example.milkflowmovil.dominio.Stock.LECHE) "litros" else "moldes",
            )

        val actualizado = existente.copy(cantidad = maxOf(0.0, existente.cantidad + delta))
        return estado.stocks.filterNot { it.codigo == codigo } + actualizado
    }

    // ------------------------------------------------------------ VENTAS

    fun registrarVenta(
        clienteId: Long?,
        nuevoNombre: String?,
        nuevoApellido: String?,
        nuevoDni: String?,
        nuevoTipo: String?,
        moldes: Int,
        formaPago: String,
    ): ErrorApp? {
        val usuario = yo ?: return ErrorApp.SesionVencida

        if (base.actual.stockQueso < moldes) {
            return ErrorApp.Regla("Stock insuficiente de quesos. Disponibles: ${base.actual.stockQueso} moldes.")
        }

        if (clienteId == null && nuevoApellido.isNullOrBlank()) {
            return ErrorApp.Regla("Elige un cliente registrado o escribe el apellido del cliente nuevo.")
        }

        val cliente = base.actual.cliente(clienteId)
        val precio = Reglas.precioQueso(cliente, moldes, base.actual.tarifaVigente)
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val venta = Venta(
                id = idNuevo,
                clientUuid = uuid,
                recibo = "POR SINCRONIZAR",
                clienteId = clienteId ?: 0,
                vendedorId = usuario.id,
                moldes = moldes,
                precioUnitario = precio,
                total = precio * moldes,
                formaPago = formaPago,
                vendidoEn = Fechas.ahoraIso(),
                pendiente = true,
            )

            estado.copy(
                ventas = estado.ventas + venta,
                stocks = ajustarStock(estado, com.example.milkflowmovil.dominio.Stock.QUESO, -moldes.toDouble()),
            )
        }

        encolar(
            uuid, "registrar_venta",
            buildJsonObject {
                clienteId?.let { put("customer_id", it) }
                nuevoNombre?.takeIf { it.isNotBlank() }?.let { put("new_first_name", it) }
                nuevoApellido?.takeIf { it.isNotBlank() }?.let { put("new_last_name", it) }
                nuevoDni?.takeIf { it.isNotBlank() }?.let { put("new_dni_ruc", it) }
                nuevoTipo?.takeIf { it.isNotBlank() }?.let { put("new_type", it) }
                put("cheese_molds_quantity", moldes)
                put("payment_method", formaPago)
            },
            "Venta de $moldes moldes de queso",
        )

        return null
    }

    fun cerrarCaja(notas: String?): ErrorApp? {
        val usuario = yo ?: return ErrorApp.SesionVencida
        val hoy = Fechas.hoy()

        val ventasDelDia = base.actual.ventas.filter {
            it.vendidoEn.take(10) == hoy && it.cierreId == null
        }

        if (ventasDelDia.isEmpty()) {
            return ErrorApp.Regla("No hay ventas activas pendientes de cierre para el día de hoy.")
        }

        val uuid = nuevoUuid()
        val idCierre = base.reservarIdTemporal()

        base.actualizar { estado ->
            val cierre = CierreCaja(
                id = idCierre,
                clientUuid = uuid,
                date = hoy,
                cerradoPor = usuario.id,
                efectivo = ventasDelDia.filterNot { it.esDescuentoLeche }.sumOf { it.total },
                descuentoLeche = ventasDelDia.filter { it.esDescuentoLeche }.sumOf { it.total },
                total = ventasDelDia.sumOf { it.total },
                moldes = ventasDelDia.sumOf { it.moldes },
                transacciones = ventasDelDia.size,
                notes = notas,
                cerradoEn = Fechas.ahoraIso(),
                pendiente = true,
            )

            estado.copy(
                cierresCaja = estado.cierresCaja + cierre,
                ventas = estado.ventas.map {
                    if (ventasDelDia.any { venta -> venta.id == it.id }) it.copy(cierreId = idCierre) else it
                },
            )
        }

        encolar(
            uuid, "cerrar_caja",
            buildJsonObject {
                put("fecha", hoy)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
            },
            "Cierre de caja del ${Fechas.corta(hoy)}",
        )

        return null
    }

    // ----------------------------------------------------------- CALIDAD

    fun registrarAnalisis(
        productorId: Long,
        grasa: Double?,
        solidos: Double?,
        densidad: Double?,
        proteina: Double?,
        agua: Double?,
        temperatura: Double?,
        acidez: Double?,
        veredicto: String,
        notas: String?,
        agendarVisita: Boolean,
        fechaVisita: String?,
        motivoVisita: String?,
    ) {
        val usuario = yo ?: return
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                analisis = estado.analisis + Analisis(
                    id = idNuevo,
                    clientUuid = uuid,
                    productorId = productorId,
                    inspectorId = usuario.id,
                    fecha = Fechas.hoy(),
                    grasa = grasa,
                    solidos = solidos,
                    density = densidad,
                    proteina = proteina,
                    agua = agua,
                    temperature = temperatura,
                    acidez = acidez,
                    verdict = veredicto,
                    notes = notas,
                    pendiente = true,
                )
            )
        }

        encolar(
            uuid, "registrar_analisis",
            buildJsonObject {
                put("producer_id", productorId)
                put("analysis_date", Fechas.hoy())
                grasa?.let { put("fat_percentage", it) }
                solidos?.let { put("snf_percentage", it) }
                densidad?.let { put("density", it) }
                proteina?.let { put("protein_percentage", it) }
                agua?.let { put("water_addition_percentage", it) }
                temperatura?.let { put("temperature", it) }
                acidez?.let { put("ph_or_acidity", it) }
                put("verdict", veredicto)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
                if (agendarVisita) put("schedule_visit", true)
                fechaVisita?.let { put("scheduled_date", it) }
                motivoVisita?.takeIf { it.isNotBlank() }?.let { put("visit_reason", it) }
            },
            "Análisis Lactoscan de ${base.actual.usuario(productorId)?.name ?: "productor"}",
        )
    }

    fun completarVisita(visita: VisitaTecnica, informe: String) {
        if (visita.id <= 0) return
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(visitas = estado.visitas.map {
                if (it.id == visita.id) it.copy(status = "realizada", informe = informe, pendiente = true) else it
            })
        }

        encolar(
            uuid, "completar_visita",
            buildJsonObject {
                put("visit_id", visita.id)
                put("resolution_report", informe)
            },
            "Cierre de visita técnica",
        )
    }

    // ------------------------------------------------------------- ZONAS

    fun solicitarCambioZona(zonaSolicitadaId: Long, motivo: String) {
        val usuario = yo ?: return
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                solicitudesZona = estado.solicitudesZona + SolicitudZona(
                    id = idNuevo,
                    clientUuid = uuid,
                    productorId = usuario.id,
                    zonaActualId = usuario.zonaId ?: zonaSolicitadaId,
                    zonaSolicitadaId = zonaSolicitadaId,
                    status = "pendiente",
                    reason = motivo,
                    pendiente = true,
                )
            )
        }

        encolar(
            uuid, "solicitar_cambio_zona",
            buildJsonObject {
                put("requested_zone_id", zonaSolicitadaId)
                put("reason", motivo)
            },
            "Solicitud de cambio a ${base.actual.zona(zonaSolicitadaId)?.name ?: "otra zona"}",
        )
    }

    fun revisarSolicitudZona(solicitud: SolicitudZona, decision: String) {
        if (solicitud.id <= 0) return
        val usuario = yo ?: return
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(
                solicitudesZona = estado.solicitudesZona.map {
                    if (it.id == solicitud.id) {
                        it.copy(status = decision, revisadoPor = usuario.id, revisadoEn = Fechas.ahoraIso(), pendiente = true)
                    } else {
                        it
                    }
                },
                usuarios = if (decision == "aprobado") {
                    estado.usuarios.map {
                        if (it.id == solicitud.productorId) it.copy(zonaId = solicitud.zonaSolicitadaId) else it
                    }
                } else {
                    estado.usuarios
                },
            )
        }

        encolar(
            uuid, "revisar_solicitud_zona",
            buildJsonObject {
                put("request_id", solicitud.id)
                put("decision", decision)
            },
            "Solicitud de zona ${if (decision == "aprobado") "aprobada" else "rechazada"}",
        )
    }

    // -------------------------------------------------------------- PAGOS

    fun autorizarPago(productorId: Long) {
        val uuid = nuevoUuid()

        encolar(
            uuid, "autorizar_pago",
            buildJsonObject { put("producer_id", productorId) },
            "Autorización de pago a ${base.actual.usuario(productorId)?.name ?: "productor"}",
        )
    }

    fun autorizarTodosLosPagos() {
        encolar(
            nuevoUuid(), "autorizar_pago",
            buildJsonObject { put("todos", true) },
            "Autorización masiva de pagos del ciclo",
        )
    }

    fun entregarSobre(productorId: Long) {
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(liquidaciones = estado.liquidaciones.map {
                if (it.productorId == productorId && it.status == "autorizado") {
                    it.copy(status = "pagado", pagadoEn = Fechas.ahoraIso(), pagadoPor = yo?.id, pendiente = true)
                } else {
                    it
                }
            })
        }

        encolar(
            uuid, "entregar_sobre",
            buildJsonObject { put("producer_id", productorId) },
            "Entrega de sobre a ${base.actual.usuario(productorId)?.name ?: "productor"}",
        )
    }

    // ------------------------------------------------------------ SISTEMA

    fun registrarEgreso(
        categoria: String,
        descripcion: String,
        monto: Double,
        fecha: String,
        beneficiario: String?,
        personalId: Long?,
        formaPago: String,
        comprobante: String?,
    ) {
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                egresos = estado.egresos + Egreso(
                    id = idNuevo,
                    clientUuid = uuid,
                    category = categoria,
                    description = descripcion,
                    amount = monto,
                    fecha = fecha,
                    personalId = personalId,
                    beneficiario = beneficiario,
                    formaPago = formaPago,
                    comprobante = comprobante,
                    pendiente = true,
                )
            )
        }

        encolar(
            uuid, "registrar_egreso",
            buildJsonObject {
                put("category", categoria)
                put("description", descripcion)
                put("amount", monto)
                put("expense_date", fecha)
                personalId?.let { put("user_id", it) }
                beneficiario?.takeIf { it.isNotBlank() }?.let { put("beneficiary_name", it) }
                put("payment_method", formaPago)
                comprobante?.takeIf { it.isNotBlank() }?.let { put("receipt_number", it) }
            },
            "Egreso de ${monto} por $descripcion",
        )
    }

    fun actualizarTarifas(
        temporada: String,
        lecheBase: Double,
        aguaLeve: Double,
        aguaGrave: Double,
        quesoProveedor: Double,
        quesoMayorista: Double,
        quesoLocal: Double,
        notas: String?,
    ) {
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val nueva = estado.tarifaVigente.copy(
                id = idNuevo,
                temporada = temporada,
                lecheBase = lecheBase,
                lecheAguaLeve = aguaLeve,
                lecheAguaGrave = aguaGrave,
                quesoProveedor = quesoProveedor,
                quesoMayorista = quesoMayorista,
                quesoLocal = quesoLocal,
                activa = true,
                notes = notas,
            )

            estado.copy(tarifas = estado.tarifas.map { it.copy(activa = false) } + nueva)
        }

        encolar(
            uuid, "actualizar_tarifas",
            buildJsonObject {
                put("season_name", temporada)
                put("price_milk_base", lecheBase)
                put("price_milk_water_penalty_low", aguaLeve)
                put("price_milk_water_penalty_high", aguaGrave)
                put("price_cheese_provider", quesoProveedor)
                put("price_cheese_wholesale", quesoMayorista)
                put("price_cheese_local", quesoLocal)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
            },
            "Nuevas tarifas: $temporada",
        )
    }

    private companion object {
        const val ID_STOCK_LECHE = -9001L
        const val ID_STOCK_QUESO = -9002L
    }

    fun publicarAviso(titulo: String, mensaje: String, desde: String, hasta: String, rolDestino: String?) {
        val uuid = nuevoUuid()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            estado.copy(
                avisos = estado.avisos + Aviso(
                    id = idNuevo,
                    clientUuid = uuid,
                    title = titulo,
                    message = mensaje,
                    desde = desde,
                    hasta = hasta,
                    rolDestino = rolDestino,
                    pendiente = true,
                )
            )
        }

        encolar(
            uuid, "publicar_aviso",
            buildJsonObject {
                put("title", titulo)
                put("message", mensaje)
                put("start_date", desde)
                put("end_date", hasta)
                rolDestino?.takeIf { it.isNotBlank() }?.let { put("target_role", it) }
            },
            "Aviso: $titulo",
        )
    }
}
