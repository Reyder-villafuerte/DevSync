package com.example.milkflowmovil.data.local

import com.example.milkflowmovil.data.mapper.toDocumento
import com.example.milkflowmovil.data.mapper.toDomain
import com.example.milkflowmovil.data.remote.jsonMilkFlow
import com.example.milkflowmovil.domain.model.EstadoApp
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext

/** Acceso a archivos del dispositivo: lo único que cambia entre Android e iOS. */
interface AlmacenArchivos {
    fun leer(nombre: String): String?
    fun escribir(nombre: String, contenido: String)
    fun borrar(nombre: String)
}

expect fun almacenPlataforma(): AlmacenArchivos

/** Identificador estable del dispositivo, usado para nombrar el token y la cola. */
expect fun identificadorDispositivo(): String

/** Genera los client_uuid con los que viajan las operaciones. */
expect fun nuevoUuid(): String

/**
 * Base de datos local del teléfono (fuente de datos local).
 *
 * En memoria mantiene el estado de dominio (EstadoApp); en disco guarda un
 * DocumentoLocal hecho de DTO, traducido con toDocumento()/toDomain().
 *
 * Guarda un único documento JSON con todo el estado sincronizado y la cola de
 * operaciones. A la escala de Huata (decenas de productores, cientos de
 * entregas por semana) esto cabe de sobra en memoria y evita arrastrar un
 * motor SQL y su generador de código al proyecto; a cambio, cada cambio
 * reescribe el archivo completo, por eso el guardado es asíncrono y serializado.
 */
class BaseLocal(
    private val archivos: AlmacenArchivos = almacenPlataforma(),
    private val alcance: CoroutineScope,
) {
    private val json = jsonMilkFlow

    private val candado = Mutex()
    private val _estado = MutableStateFlow(EstadoApp())

    val estado: StateFlow<EstadoApp> = _estado.asStateFlow()

    val actual: EstadoApp get() = _estado.value

    suspend fun cargar() {
        val contenido = withContext(Dispatchers.Default) { archivos.leer(ARCHIVO) } ?: return
        val recuperado = runCatching { json.decodeFromString<DocumentoLocal>(contenido) }.getOrNull() ?: return
        _estado.value = recuperado.toDomain()
    }

    /** Aplica un cambio y lo persiste. El bloque debe ser puro. */
    fun actualizar(bloque: (EstadoApp) -> EstadoApp) {
        _estado.value = bloque(_estado.value)
        guardar()
    }

    /** Reserva un id negativo para una fila creada sin señal. */
    fun reservarIdTemporal(): Long {
        var id = 0L
        actualizar { estado ->
            id = estado.proximoIdTemporal
            estado.copy(proximoIdTemporal = estado.proximoIdTemporal - 1)
        }
        return id
    }

    /** Borra los datos del usuario anterior: la base local es de una sola persona. */
    fun limpiar(urlBase: String) {
        _estado.value = EstadoApp(urlBase = urlBase)
        guardar()
    }

    private fun guardar() {
        val instantanea = _estado.value
        alcance.launch {
            candado.withLock {
                withContext(Dispatchers.Default) {
                    runCatching { archivos.escribir(ARCHIVO, json.encodeToString(instantanea.toDocumento())) }
                }
            }
        }
    }

    private companion object {
        const val ARCHIVO = "milkflow-local.json"
    }
}
