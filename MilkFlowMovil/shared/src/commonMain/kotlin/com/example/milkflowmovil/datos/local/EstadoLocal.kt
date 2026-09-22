package com.example.milkflowmovil.datos.local

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
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject

/** Sesión activa guardada en el dispositivo. Sobrevive al cierre de la app. */
@Serializable
data class Sesion(
    val token: String,
    val usuario: Usuario,
    val dispositivoId: String,
    val iniciadaEn: String,
)

/** Una operación esperando señal para subir. */
@Serializable
data class OperacionPendiente(
    val clientUuid: String,
    val comando: String,
    val payload: JsonObject,
    val descripcion: String,
    val creadaEn: String,
    val intentos: Int = 0,
    val ultimoError: String? = null,
)

/** Operación que el servidor rechazó por regla de negocio: no se reintenta. */
@Serializable
data class OperacionRechazada(
    val clientUuid: String,
    val comando: String,
    val descripcion: String,
    val motivo: String,
    val rechazadaEn: String,
)

/**
 * Todo lo que la app sabe, guardado en el teléfono.
 *
 * Esta es la única fuente de datos de la interfaz: las pantallas jamás llaman
 * a la red. La sincronización llena este estado; sin señal, sigue sirviendo.
 */
@Serializable
data class EstadoLocal(
    val sesion: Sesion? = null,
    val urlBase: String = URL_POR_DEFECTO,

    val zonas: List<Zona> = emptyList(),
    val usuarios: List<Usuario> = emptyList(),
    val tarifas: List<Tarifa> = emptyList(),
    val stocks: List<Stock> = emptyList(),
    val rutas: List<Ruta> = emptyList(),
    val entregas: List<Entrega> = emptyList(),
    val recepciones: List<Recepcion> = emptyList(),
    val clientes: List<Cliente> = emptyList(),
    val ventas: List<Venta> = emptyList(),
    val cierresCaja: List<CierreCaja> = emptyList(),
    val analisis: List<Analisis> = emptyList(),
    val visitas: List<VisitaTecnica> = emptyList(),
    val solicitudesZona: List<SolicitudZona> = emptyList(),
    val liquidaciones: List<Liquidacion> = emptyList(),
    val descuentos: List<Descuento> = emptyList(),
    val egresos: List<Egreso> = emptyList(),
    val avisos: List<Aviso> = emptyList(),

    val cursores: Map<String, String> = emptyMap(),
    val cola: List<OperacionPendiente> = emptyList(),
    val rechazadas: List<OperacionRechazada> = emptyList(),

    val proximoIdTemporal: Long = -1,
    val ultimaSincronizacion: String? = null,
    val ultimoErrorSync: String? = null,
) {
    val tarifaVigente: Tarifa
        get() = tarifas.filter { it.activa }.maxByOrNull { it.id }
            ?: tarifas.maxByOrNull { it.id }
            ?: Tarifa(id = 0, temporada = "Temporada Huata")

    val stockLeche: Double get() = stocks.firstOrNull { it.codigo == Stock.LECHE }?.cantidad ?: 0.0
    val stockQueso: Double get() = stocks.firstOrNull { it.codigo == Stock.QUESO }?.cantidad ?: 0.0

    val productores: List<Usuario> get() = usuarios.filter { it.role == "productor" }.sortedBy { it.name }
    val acopiadores: List<Usuario> get() = usuarios.filter { it.role == "acopiador" }.sortedBy { it.name }

    fun usuario(id: Long?): Usuario? = usuarios.firstOrNull { it.id == id }
    fun zona(id: Long?): Zona? = zonas.firstOrNull { it.id == id }
    fun ruta(id: Long?): Ruta? = rutas.firstOrNull { it.id == id }
    fun cliente(id: Long?): Cliente? = clientes.firstOrNull { it.id == id }

    fun entregasDeRuta(rutaId: Long): List<Entrega> = entregas.filter { it.rutaId == rutaId }

    fun recepcionDeRuta(rutaId: Long): Recepcion? = recepciones.firstOrNull { it.rutaId == rutaId }

    companion object {
        /** El emulador de Android alcanza el host del desarrollador en 10.0.2.2. */
        const val URL_POR_DEFECTO = "http://10.0.2.2:8000"
    }
}

/**
 * Sobre semanal de un productor calculado con los datos que ya están en el
 * teléfono. Es lo que ve el pagador en la ruta del viernes, sin señal.
 */
fun EstadoLocal.sobreDe(productorId: Long): com.example.milkflowmovil.dominio.Reglas.Sobre =
    com.example.milkflowmovil.dominio.Reglas.calcularSobre(
        productorId = productorId,
        entregas = entregas.filter { it.productorId == productorId },
        rutas = rutas.associateBy { it.id },
        analisis = analisis.filter { it.productorId == productorId },
        descuentos = descuentos.filter { it.productorId == productorId },
        liquidaciones = liquidaciones.filter { it.productorId == productorId },
        tarifa = tarifaVigente,
    )
