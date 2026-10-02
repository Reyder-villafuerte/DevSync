package com.example.milkflowmovil.data.mapper

import com.example.milkflowmovil.data.remote.dto.AnalisisDto
import com.example.milkflowmovil.data.remote.dto.AvisoDto
import com.example.milkflowmovil.data.remote.dto.CierreCajaDto
import com.example.milkflowmovil.data.remote.dto.ClienteDto
import com.example.milkflowmovil.data.remote.dto.DescuentoDto
import com.example.milkflowmovil.data.remote.dto.EgresoDto
import com.example.milkflowmovil.data.remote.dto.EntregaDto
import com.example.milkflowmovil.data.remote.dto.LiquidacionDto
import com.example.milkflowmovil.data.remote.dto.RecepcionDto
import com.example.milkflowmovil.data.remote.dto.RutaDto
import com.example.milkflowmovil.data.remote.dto.SolicitudZonaDto
import com.example.milkflowmovil.data.remote.dto.StockDto
import com.example.milkflowmovil.data.remote.dto.TarifaDto
import com.example.milkflowmovil.data.remote.dto.UsuarioDto
import com.example.milkflowmovil.data.remote.dto.VentaDto
import com.example.milkflowmovil.data.remote.dto.VisitaTecnicaDto
import com.example.milkflowmovil.data.remote.dto.ZonaDto
import com.example.milkflowmovil.domain.model.Analisis
import com.example.milkflowmovil.domain.model.Aviso
import com.example.milkflowmovil.domain.model.CierreCaja
import com.example.milkflowmovil.domain.model.Cliente
import com.example.milkflowmovil.domain.model.Descuento
import com.example.milkflowmovil.domain.model.Egreso
import com.example.milkflowmovil.domain.model.Entrega
import com.example.milkflowmovil.domain.model.Liquidacion
import com.example.milkflowmovil.domain.model.Recepcion
import com.example.milkflowmovil.domain.model.Ruta
import com.example.milkflowmovil.domain.model.SolicitudZona
import com.example.milkflowmovil.domain.model.Stock
import com.example.milkflowmovil.domain.model.Tarifa
import com.example.milkflowmovil.domain.model.Usuario
import com.example.milkflowmovil.domain.model.Venta
import com.example.milkflowmovil.domain.model.VisitaTecnica
import com.example.milkflowmovil.domain.model.Zona

/*
 * Traducción DTO <-> dominio en un solo lugar.
 *
 * toDomain(): lo que llega de la API (o del archivo local) se convierte en
 * modelo de dominio. toDto(): el camino inverso, para guardar en el teléfono.
 */

fun ZonaDto.toDomain(): Zona = Zona(
    id = id,
    code = code,
    name = name,
    description = description,
    activa = activa,
    actualizadoEn = actualizadoEn,
)

fun Zona.toDto(): ZonaDto = ZonaDto(
    id = id,
    code = code,
    name = name,
    description = description,
    activa = activa,
    actualizadoEn = actualizadoEn,
)

fun UsuarioDto.toDomain(): Usuario = Usuario(
    id = id,
    name = name,
    email = email,
    role = role,
    phone = phone,
    dni = dni,
    zonaId = zonaId,
    activo = activo,
    actualizadoEn = actualizadoEn,
)

fun Usuario.toDto(): UsuarioDto = UsuarioDto(
    id = id,
    name = name,
    email = email,
    role = role,
    phone = phone,
    dni = dni,
    zonaId = zonaId,
    activo = activo,
    actualizadoEn = actualizadoEn,
)

fun TarifaDto.toDomain(): Tarifa = Tarifa(
    id = id,
    temporada = temporada,
    lecheBase = lecheBase,
    lecheAguaLeve = lecheAguaLeve,
    lecheAguaGrave = lecheAguaGrave,
    quesoProveedor = quesoProveedor,
    quesoMayorista = quesoMayorista,
    quesoLocal = quesoLocal,
    activa = activa,
    notes = notes,
    actualizadoEn = actualizadoEn,
)

fun Tarifa.toDto(): TarifaDto = TarifaDto(
    id = id,
    temporada = temporada,
    lecheBase = lecheBase,
    lecheAguaLeve = lecheAguaLeve,
    lecheAguaGrave = lecheAguaGrave,
    quesoProveedor = quesoProveedor,
    quesoMayorista = quesoMayorista,
    quesoLocal = quesoLocal,
    activa = activa,
    notes = notes,
    actualizadoEn = actualizadoEn,
)

fun StockDto.toDomain(): Stock = Stock(
    id = id,
    codigo = codigo,
    nombre = nombre,
    cantidad = cantidad,
    unit = unit,
    actualizadoEn = actualizadoEn,
)

fun Stock.toDto(): StockDto = StockDto(
    id = id,
    codigo = codigo,
    nombre = nombre,
    cantidad = cantidad,
    unit = unit,
    actualizadoEn = actualizadoEn,
)

fun RutaDto.toDomain(): Ruta = Ruta(
    id = id,
    clientUuid = clientUuid,
    date = date,
    zonaId = zonaId,
    acopiadorId = acopiadorId,
    horaInicio = horaInicio,
    status = status,
    litrosTotales = litrosTotales,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Ruta.toDto(): RutaDto = RutaDto(
    id = id,
    clientUuid = clientUuid,
    date = date,
    zonaId = zonaId,
    acopiadorId = acopiadorId,
    horaInicio = horaInicio,
    status = status,
    litrosTotales = litrosTotales,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun EntregaDto.toDomain(): Entrega = Entrega(
    id = id,
    clientUuid = clientUuid,
    rutaId = rutaId,
    productorId = productorId,
    liters = liters,
    hora = hora,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
    rutaClientUuid = rutaClientUuid,
)

fun Entrega.toDto(): EntregaDto = EntregaDto(
    id = id,
    clientUuid = clientUuid,
    rutaId = rutaId,
    productorId = productorId,
    liters = liters,
    hora = hora,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
    rutaClientUuid = rutaClientUuid,
)

fun RecepcionDto.toDomain(): Recepcion = Recepcion(
    id = id,
    clientUuid = clientUuid,
    rutaId = rutaId,
    verificadorId = verificadorId,
    litrosDeclarados = litrosDeclarados,
    litrosCaudalimetro = litrosCaudalimetro,
    diferencia = diferencia,
    estado = estado,
    observation = observation,
    verificadoEn = verificadoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Recepcion.toDto(): RecepcionDto = RecepcionDto(
    id = id,
    clientUuid = clientUuid,
    rutaId = rutaId,
    verificadorId = verificadorId,
    litrosDeclarados = litrosDeclarados,
    litrosCaudalimetro = litrosCaudalimetro,
    diferencia = diferencia,
    estado = estado,
    observation = observation,
    verificadoEn = verificadoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun ClienteDto.toDomain(): Cliente = Cliente(
    id = id,
    clientUuid = clientUuid,
    nombres = nombres,
    apellidos = apellidos,
    dniRuc = dniRuc,
    phone = phone,
    type = type,
    usuarioVinculadoId = usuarioVinculadoId,
    mayoristaAprobado = mayoristaAprobado,
    actualizadoEn = actualizadoEn,
)

fun Cliente.toDto(): ClienteDto = ClienteDto(
    id = id,
    clientUuid = clientUuid,
    nombres = nombres,
    apellidos = apellidos,
    dniRuc = dniRuc,
    phone = phone,
    type = type,
    usuarioVinculadoId = usuarioVinculadoId,
    mayoristaAprobado = mayoristaAprobado,
    actualizadoEn = actualizadoEn,
)

fun VentaDto.toDomain(): Venta = Venta(
    id = id,
    clientUuid = clientUuid,
    recibo = recibo,
    clienteId = clienteId,
    vendedorId = vendedorId,
    cierreId = cierreId,
    moldes = moldes,
    precioUnitario = precioUnitario,
    total = total,
    formaPago = formaPago,
    vendidoEn = vendidoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Venta.toDto(): VentaDto = VentaDto(
    id = id,
    clientUuid = clientUuid,
    recibo = recibo,
    clienteId = clienteId,
    vendedorId = vendedorId,
    cierreId = cierreId,
    moldes = moldes,
    precioUnitario = precioUnitario,
    total = total,
    formaPago = formaPago,
    vendidoEn = vendidoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun CierreCajaDto.toDomain(): CierreCaja = CierreCaja(
    id = id,
    clientUuid = clientUuid,
    date = date,
    cerradoPor = cerradoPor,
    efectivo = efectivo,
    descuentoLeche = descuentoLeche,
    total = total,
    moldes = moldes,
    transacciones = transacciones,
    notes = notes,
    cerradoEn = cerradoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun CierreCaja.toDto(): CierreCajaDto = CierreCajaDto(
    id = id,
    clientUuid = clientUuid,
    date = date,
    cerradoPor = cerradoPor,
    efectivo = efectivo,
    descuentoLeche = descuentoLeche,
    total = total,
    moldes = moldes,
    transacciones = transacciones,
    notes = notes,
    cerradoEn = cerradoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun AnalisisDto.toDomain(): Analisis = Analisis(
    id = id,
    clientUuid = clientUuid,
    productorId = productorId,
    inspectorId = inspectorId,
    fecha = fecha,
    grasa = grasa,
    solidos = solidos,
    density = density,
    proteina = proteina,
    agua = agua,
    temperature = temperature,
    acidez = acidez,
    verdict = verdict,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Analisis.toDto(): AnalisisDto = AnalisisDto(
    id = id,
    clientUuid = clientUuid,
    productorId = productorId,
    inspectorId = inspectorId,
    fecha = fecha,
    grasa = grasa,
    solidos = solidos,
    density = density,
    proteina = proteina,
    agua = agua,
    temperature = temperature,
    acidez = acidez,
    verdict = verdict,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun VisitaTecnicaDto.toDomain(): VisitaTecnica = VisitaTecnica(
    id = id,
    clientUuid = clientUuid,
    analisisId = analisisId,
    productorId = productorId,
    inspectorId = inspectorId,
    fecha = fecha,
    hora = hora,
    status = status,
    reason = reason,
    informe = informe,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun VisitaTecnica.toDto(): VisitaTecnicaDto = VisitaTecnicaDto(
    id = id,
    clientUuid = clientUuid,
    analisisId = analisisId,
    productorId = productorId,
    inspectorId = inspectorId,
    fecha = fecha,
    hora = hora,
    status = status,
    reason = reason,
    informe = informe,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun SolicitudZonaDto.toDomain(): SolicitudZona = SolicitudZona(
    id = id,
    clientUuid = clientUuid,
    productorId = productorId,
    zonaActualId = zonaActualId,
    zonaSolicitadaId = zonaSolicitadaId,
    status = status,
    reason = reason,
    revisadoPor = revisadoPor,
    revisadoEn = revisadoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun SolicitudZona.toDto(): SolicitudZonaDto = SolicitudZonaDto(
    id = id,
    clientUuid = clientUuid,
    productorId = productorId,
    zonaActualId = zonaActualId,
    zonaSolicitadaId = zonaSolicitadaId,
    status = status,
    reason = reason,
    revisadoPor = revisadoPor,
    revisadoEn = revisadoEn,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun LiquidacionDto.toDomain(): Liquidacion = Liquidacion(
    id = id,
    clientUuid = clientUuid,
    codigo = codigo,
    productorId = productorId,
    desde = desde,
    hasta = hasta,
    litros = litros,
    precioLitro = precioLitro,
    bruto = bruto,
    deducciones = deducciones,
    neto = neto,
    status = status,
    pagadoEn = pagadoEn,
    formaPago = formaPago,
    pagadoPor = pagadoPor,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Liquidacion.toDto(): LiquidacionDto = LiquidacionDto(
    id = id,
    clientUuid = clientUuid,
    codigo = codigo,
    productorId = productorId,
    desde = desde,
    hasta = hasta,
    litros = litros,
    precioLitro = precioLitro,
    bruto = bruto,
    deducciones = deducciones,
    neto = neto,
    status = status,
    pagadoEn = pagadoEn,
    formaPago = formaPago,
    pagadoPor = pagadoPor,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun DescuentoDto.toDomain(): Descuento = Descuento(
    id = id,
    clientUuid = clientUuid,
    productorId = productorId,
    liquidacionId = liquidacionId,
    ventaId = ventaId,
    date = date,
    concept = concept,
    amount = amount,
    status = status,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Descuento.toDto(): DescuentoDto = DescuentoDto(
    id = id,
    clientUuid = clientUuid,
    productorId = productorId,
    liquidacionId = liquidacionId,
    ventaId = ventaId,
    date = date,
    concept = concept,
    amount = amount,
    status = status,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun EgresoDto.toDomain(): Egreso = Egreso(
    id = id,
    clientUuid = clientUuid,
    category = category,
    description = description,
    amount = amount,
    fecha = fecha,
    personalId = personalId,
    beneficiario = beneficiario,
    formaPago = formaPago,
    comprobante = comprobante,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Egreso.toDto(): EgresoDto = EgresoDto(
    id = id,
    clientUuid = clientUuid,
    category = category,
    description = description,
    amount = amount,
    fecha = fecha,
    personalId = personalId,
    beneficiario = beneficiario,
    formaPago = formaPago,
    comprobante = comprobante,
    notes = notes,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun AvisoDto.toDomain(): Aviso = Aviso(
    id = id,
    clientUuid = clientUuid,
    title = title,
    message = message,
    desde = desde,
    hasta = hasta,
    rolDestino = rolDestino,
    usuarioDestinoId = usuarioDestinoId,
    activo = activo,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)

fun Aviso.toDto(): AvisoDto = AvisoDto(
    id = id,
    clientUuid = clientUuid,
    title = title,
    message = message,
    desde = desde,
    hasta = hasta,
    rolDestino = rolDestino,
    usuarioDestinoId = usuarioDestinoId,
    activo = activo,
    actualizadoEn = actualizadoEn,
    pendiente = pendiente,
)
