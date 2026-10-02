package com.example.milkflowmovil.data.local

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
import com.example.milkflowmovil.domain.model.EstadoApp
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject

/**
 * Forma en que el estado se guarda en el archivo `milkflow-local.json`.
 *
 * Usa los mismos DTO que la API, así una fila bajada del servidor y una
 * creada sin señal se guardan igual. Los nombres de las propiedades son los
 * mismos de versiones anteriores de la app: un teléfono que ya tenía datos
 * los sigue leyendo después de actualizar.
 */
@Serializable
data class DocumentoLocal(
    val sesion: SesionDto? = null,
    val urlBase: String = EstadoApp.URL_POR_DEFECTO,

    val zonas: List<ZonaDto> = emptyList(),
    val usuarios: List<UsuarioDto> = emptyList(),
    val tarifas: List<TarifaDto> = emptyList(),
    val stocks: List<StockDto> = emptyList(),
    val rutas: List<RutaDto> = emptyList(),
    val entregas: List<EntregaDto> = emptyList(),
    val recepciones: List<RecepcionDto> = emptyList(),
    val clientes: List<ClienteDto> = emptyList(),
    val ventas: List<VentaDto> = emptyList(),
    val cierresCaja: List<CierreCajaDto> = emptyList(),
    val analisis: List<AnalisisDto> = emptyList(),
    val visitas: List<VisitaTecnicaDto> = emptyList(),
    val solicitudesZona: List<SolicitudZonaDto> = emptyList(),
    val liquidaciones: List<LiquidacionDto> = emptyList(),
    val descuentos: List<DescuentoDto> = emptyList(),
    val egresos: List<EgresoDto> = emptyList(),
    val avisos: List<AvisoDto> = emptyList(),

    val cursores: Map<String, String> = emptyMap(),
    val cola: List<OperacionPendienteDto> = emptyList(),
    val rechazadas: List<OperacionRechazadaDto> = emptyList(),

    val proximoIdTemporal: Long = -1,
    val ultimaSincronizacion: String? = null,
    val ultimoErrorSync: String? = null,
)

@Serializable
data class SesionDto(
    val token: String,
    val usuario: UsuarioDto,
    val dispositivoId: String,
    val iniciadaEn: String,
)

@Serializable
data class OperacionPendienteDto(
    val clientUuid: String,
    val comando: String,
    val payload: JsonObject,
    val descripcion: String,
    val creadaEn: String,
    val intentos: Int = 0,
    val ultimoError: String? = null,
)

@Serializable
data class OperacionRechazadaDto(
    val clientUuid: String,
    val comando: String,
    val descripcion: String,
    val motivo: String,
    val rechazadaEn: String,
)
