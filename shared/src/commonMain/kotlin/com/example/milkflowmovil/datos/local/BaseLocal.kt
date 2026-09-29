package com.example.milkflowmovil.datos.local

import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json

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
 * Base de datos local del teléfono.
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
    private val json = Json {
        ignoreUnknownKeys = true
        encodeDefaults = true
        isLenient = true
    }

    private val candado = Mutex()
    private val _estado = MutableStateFlow(EstadoLocal())

    val estado: StateFlow<EstadoLocal> = _estado.asStateFlow()

    val actual: EstadoLocal get() = _estado.value

    suspend fun cargar() {
        val contenido = withContext(Dispatchers.Default) { archivos.leer(ARCHIVO) } ?: return
        val recuperado = runCatching { json.decodeFromString<EstadoLocal>(contenido) }.getOrNull() ?: return
        _estado.value = recuperado
    }

    /** Aplica un cambio y lo persiste. El bloque debe ser puro. */
    fun actualizar(bloque: (EstadoLocal) -> EstadoLocal) {
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
        _estado.value = EstadoLocal(urlBase = urlBase)
        guardar()
    }

    private fun guardar() {
        val instantanea = _estado.value
        alcance.launch {
            candado.withLock {
                withContext(Dispatchers.Default) {
                    runCatching { archivos.escribir(ARCHIVO, json.encodeToString(instantanea)) }
                }
            }
        }
    }

    private companion object {
        const val ARCHIVO = "milkflow-local.json"
    }
}
