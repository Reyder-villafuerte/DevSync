package pe.edu.upeu.milkflow.data.local

import pe.edu.upeu.milkflow.data.local.db.Avisos
import pe.edu.upeu.milkflow.data.local.db.Inspecciones
import pe.edu.upeu.milkflow.data.local.db.Jornadas
import pe.edu.upeu.milkflow.data.local.db.Liquidaciones
import pe.edu.upeu.milkflow.data.local.db.Outbox
import pe.edu.upeu.milkflow.data.local.db.Precios
import pe.edu.upeu.milkflow.data.local.db.Productores
import pe.edu.upeu.milkflow.data.local.db.Recolecciones
import pe.edu.upeu.milkflow.data.local.db.Rutas
import pe.edu.upeu.milkflow.data.local.db.Sanciones
import pe.edu.upeu.milkflow.data.local.db.Solicitudes_ruta
import pe.edu.upeu.milkflow.data.local.db.Zonas
import pe.edu.upeu.milkflow.domain.model.Aviso
import pe.edu.upeu.milkflow.domain.model.ConceptoPrecio
import pe.edu.upeu.milkflow.domain.model.DictamenCalidad
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.EstadoProductor
import pe.edu.upeu.milkflow.domain.model.EstadoRecepcion
import pe.edu.upeu.milkflow.domain.model.EstadoSolicitud
import pe.edu.upeu.milkflow.domain.model.Inspeccion
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Liquidacion
import pe.edu.upeu.milkflow.domain.model.Precio
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.model.Ruta
import pe.edu.upeu.milkflow.domain.model.Sancion
import pe.edu.upeu.milkflow.domain.model.SolicitudRuta
import pe.edu.upeu.milkflow.domain.model.TipoCliente
import pe.edu.upeu.milkflow.domain.model.TipoSancion
import pe.edu.upeu.milkflow.domain.model.Zona
import pe.edu.upeu.milkflow.domain.sync.OperacionOutbox
import pe.edu.upeu.milkflow.domain.sync.TipoOperacion
import pe.edu.upeu.milkflow.domain.vo.Dni
import pe.edu.upeu.milkflow.domain.vo.Litros
import pe.edu.upeu.milkflow.domain.vo.MedicionLactoscan

/*
 * Row (SQLDelight) -> entidad de dominio. Los nombres de columna son los del
 * .sq (snake_case). Los valores ya validados se reconstruyen con `confiar`.
 */

internal fun Rutas.aDominio() = Ruta(id, nombre, codigo, activa.aBoolean(), updated_at.aInstant(), version, deleted.aBoolean())

internal fun Zonas.aDominio() = Zona(id, nombre, codigo, ruta_id, activa.aBoolean(), updated_at.aInstant(), version, deleted.aBoolean())

internal fun Productores.aDominio() = Productor(
    id = id,
    codigoPadron = codigo_padron,
    nombres = nombres,
    apellidos = apellidos,
    dni = Dni.confiar(dni),
    zonaId = zona_id,
    telefono = telefono,
    estado = EstadoProductor.desde(estado),
    fechaIngreso = fecha_ingreso.aLocalDate(),
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Jornadas.aDominio() = JornadaRuta(
    id = id,
    acopiadorId = acopiador_id,
    rutaId = ruta_id,
    dispositivoId = dispositivo_id,
    fecha = fecha.aLocalDate(),
    horaInicio = hora_inicio.aInstant(),
    horaCierre = hora_cierre.aInstantOrNull(),
    litrosDeclarados = Litros.confiar(litros_declarados),
    estado = EstadoJornada.desde(estado),
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Recolecciones.aDominio() = Recoleccion(
    id = id,
    jornadaId = jornada_id,
    productorId = productor_id,
    litros = Litros.confiar(litros),
    horaRegistro = hora_registro.aInstant(),
    observacion = observacion,
    sospechaAdulteracion = sospecha_adulteracion.aBoolean(),
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
    estadoRecepcion = EstadoRecepcion.desde(estado_recepcion),
    litrosRecibidos = litros_recibidos?.let { Litros.confiar(it) },
    litrosFaltantes = Litros.confiar(litros_faltantes),
)

internal fun Inspecciones.aDominio() = Inspeccion(
    id = id,
    productorId = productor_id,
    supervisorId = supervisor_id,
    jornadaId = jornada_id,
    recoleccionId = recoleccion_id,
    tomadoEn = tomado_en.aInstant(),
    medicion = MedicionLactoscan(agua_pct, ph, densidad, grasa_pct, solidos_ng_pct, temperatura),
    dictamen = DictamenCalidad.desde(dictamen),
    dictamenDetalle = dictamen_detalle,
    esReincidencia = es_reincidencia.aBoolean(),
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Sanciones.aDominio() = Sancion(
    id = id,
    productorId = productor_id,
    inspeccionId = inspeccion_id,
    semanaPagoId = semana_pago_id,
    tipo = TipoSancion.desde(tipo) ?: TipoSancion.ADVERTENCIA_DESCUENTO,
    porcentajeDescuento = porcentaje_descuento,
    montoDescuento = monto_descuento,
    tarifaDegradadaLitro = tarifa_degradada_litro,
    retiraDelPadron = retira_del_padron.aBoolean(),
    expulsa = expulsa.aBoolean(),
    estado = estado,
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Avisos.aDominio() = Aviso(
    id = id,
    titulo = titulo,
    contenido = contenido,
    imagenUrl = imagen_url,
    obligatorio = obligatorio.aBoolean(),
    fechaPublicacion = fecha_publicacion.aInstant(),
    fechaExpiracion = fecha_expiracion.aInstantOrNull(),
    vistoLocalmente = visto_localmente.aBoolean(),
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Precios.aDominio() = Precio(
    id = id,
    concepto = if (concepto == "COMPRA_LECHE") ConceptoPrecio.COMPRA_LECHE else ConceptoPrecio.VENTA,
    productoId = producto_id,
    tipoCliente = tipo_cliente?.let(TipoCliente::desde),
    valor = valor,
    valorMinimo = valor_minimo,
    vigenteDesde = vigente_desde.aLocalDate(),
    vigenteHasta = vigente_hasta?.aLocalDate(),
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Liquidaciones.aDominio() = Liquidacion(
    id = id,
    semanaPagoId = semana_pago_id,
    productorId = productor_id,
    litrosTotales = Litros.confiar(litros_totales),
    precioLitroAplicado = precio_litro_aplicado,
    tarifaDegradada = tarifa_degradada.aBoolean(),
    montoBruto = pe.edu.upeu.milkflow.domain.vo.Dinero.confiar(monto_bruto),
    totalDescuentos = pe.edu.upeu.milkflow.domain.vo.Dinero.confiar(total_descuentos),
    montoNeto = pe.edu.upeu.milkflow.domain.vo.Dinero.confiar(monto_neto),
    estado = estado,
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Solicitudes_ruta.aDominio() = SolicitudRuta(
    id = id,
    productorId = productor_id,
    zonaActualId = zona_actual_id,
    zonaSolicitadaId = zona_solicitada_id,
    motivo = motivo,
    estado = EstadoSolicitud.desde(estado),
    resueltoEn = resuelto_en.aInstantOrNull(),
    comentarioResolucion = comentario_resolucion,
    updatedAt = updated_at.aInstant(),
    version = version,
    deleted = deleted.aBoolean(),
)

internal fun Outbox.aDominio() = OperacionOutbox(
    idLocal = id_local,
    operacion = TipoOperacion.valueOf(operacion),
    tabla = tabla,
    idRegistro = id_registro,
    payloadJson = payload_json,
    versionBase = version_base,
    intentos = intentos.toInt(),
    ultimoError = ultimo_error,
    creadoEn = creado_en.aInstant(),
)
