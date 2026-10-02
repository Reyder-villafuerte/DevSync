package com.example.milkflowmovil.data.mapper

import com.example.milkflowmovil.data.local.DocumentoLocal
import com.example.milkflowmovil.data.local.OperacionPendienteDto
import com.example.milkflowmovil.data.local.OperacionRechazadaDto
import com.example.milkflowmovil.data.local.SesionDto
import com.example.milkflowmovil.domain.model.EstadoApp
import com.example.milkflowmovil.domain.model.OperacionPendiente
import com.example.milkflowmovil.domain.model.OperacionRechazada
import com.example.milkflowmovil.domain.model.Sesion
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.jsonObject

/*
 * Traducción entre el archivo guardado en el teléfono (DocumentoLocal) y el
 * estado de dominio (EstadoApp) que usa toda la app.
 */

fun DocumentoLocal.toDomain(): EstadoApp = EstadoApp(
    sesion = sesion?.toDomain(),
    urlBase = urlBase,
    zonas = zonas.map { it.toDomain() },
    usuarios = usuarios.map { it.toDomain() },
    tarifas = tarifas.map { it.toDomain() },
    stocks = stocks.map { it.toDomain() },
    rutas = rutas.map { it.toDomain() },
    entregas = entregas.map { it.toDomain() },
    recepciones = recepciones.map { it.toDomain() },
    clientes = clientes.map { it.toDomain() },
    ventas = ventas.map { it.toDomain() },
    cierresCaja = cierresCaja.map { it.toDomain() },
    analisis = analisis.map { it.toDomain() },
    visitas = visitas.map { it.toDomain() },
    solicitudesZona = solicitudesZona.map { it.toDomain() },
    liquidaciones = liquidaciones.map { it.toDomain() },
    descuentos = descuentos.map { it.toDomain() },
    egresos = egresos.map { it.toDomain() },
    avisos = avisos.map { it.toDomain() },
    cursores = cursores,
    cola = cola.map { it.toDomain() },
    rechazadas = rechazadas.map { it.toDomain() },
    proximoIdTemporal = proximoIdTemporal,
    ultimaSincronizacion = ultimaSincronizacion,
    ultimoErrorSync = ultimoErrorSync,
)

fun EstadoApp.toDocumento(): DocumentoLocal = DocumentoLocal(
    sesion = sesion?.toDto(),
    urlBase = urlBase,
    zonas = zonas.map { it.toDto() },
    usuarios = usuarios.map { it.toDto() },
    tarifas = tarifas.map { it.toDto() },
    stocks = stocks.map { it.toDto() },
    rutas = rutas.map { it.toDto() },
    entregas = entregas.map { it.toDto() },
    recepciones = recepciones.map { it.toDto() },
    clientes = clientes.map { it.toDto() },
    ventas = ventas.map { it.toDto() },
    cierresCaja = cierresCaja.map { it.toDto() },
    analisis = analisis.map { it.toDto() },
    visitas = visitas.map { it.toDto() },
    solicitudesZona = solicitudesZona.map { it.toDto() },
    liquidaciones = liquidaciones.map { it.toDto() },
    descuentos = descuentos.map { it.toDto() },
    egresos = egresos.map { it.toDto() },
    avisos = avisos.map { it.toDto() },
    cursores = cursores,
    cola = cola.map { it.toDto() },
    rechazadas = rechazadas.map { it.toDto() },
    proximoIdTemporal = proximoIdTemporal,
    ultimaSincronizacion = ultimaSincronizacion,
    ultimoErrorSync = ultimoErrorSync,
)

fun SesionDto.toDomain(): Sesion = Sesion(
    token = token,
    usuario = usuario.toDomain(),
    dispositivoId = dispositivoId,
    iniciadaEn = iniciadaEn,
)

fun Sesion.toDto(): SesionDto = SesionDto(
    token = token,
    usuario = usuario.toDto(),
    dispositivoId = dispositivoId,
    iniciadaEn = iniciadaEn,
)

fun OperacionPendienteDto.toDomain(): OperacionPendiente = OperacionPendiente(
    clientUuid = clientUuid,
    comando = comando,
    payload = payload.toString(),
    descripcion = descripcion,
    creadaEn = creadaEn,
    intentos = intentos,
    ultimoError = ultimoError,
)

fun OperacionPendiente.toDto(): OperacionPendienteDto = OperacionPendienteDto(
    clientUuid = clientUuid,
    comando = comando,
    payload = payloadComoJson(),
    descripcion = descripcion,
    creadaEn = creadaEn,
    intentos = intentos,
    ultimoError = ultimoError,
)

/** El cuerpo guardado como texto vuelve a ser un objeto JSON para subirlo. */
fun OperacionPendiente.payloadComoJson(): JsonObject =
    runCatching { Json.parseToJsonElement(payload).jsonObject }.getOrElse { JsonObject(emptyMap()) }

fun OperacionRechazadaDto.toDomain(): OperacionRechazada = OperacionRechazada(
    clientUuid = clientUuid,
    comando = comando,
    descripcion = descripcion,
    motivo = motivo,
    rechazadaEn = rechazadaEn,
)

fun OperacionRechazada.toDto(): OperacionRechazadaDto = OperacionRechazadaDto(
    clientUuid = clientUuid,
    comando = comando,
    descripcion = descripcion,
    motivo = motivo,
    rechazadaEn = rechazadaEn,
)
