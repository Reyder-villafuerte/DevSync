package pe.edu.upeu.milkflow.domain.sync

import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.repository.SesionRepository

/**
 * Orquesta el ciclo completo de sincronización.
 *
 * Orden (importante): PRIMERO se sube el outbox, LUEGO se baja el delta. Así el
 * servidor ya tiene lo que el dispositivo generó antes de que el dispositivo
 * reciba el estado del servidor, y la bajada trae de vuelta esas mismas filas
 * ya con su `version` y su `updated_at` definitivos.
 *
 * Garantías:
 *  - una sola sincronización a la vez (Mutex);
 *  - toda operación de subida termina clasificada (sincronizada / descartada /
 *    conflicto / intento fallido): nunca se pierde ni se reintenta en silencio
 *    para siempre;
 *  - los deltas de bajada se paginan por cursor hasta agotarlos;
 *  - se dispara automáticamente al recuperar conectividad.
 */
class SyncManager(
    private val sincronizacion: SincronizacionRepository,
    private val cliente: ClienteSincronizacion,
    private val sesion: SesionRepository,
    private val conectividad: ObservadorConectividad,
    private val politica: PoliticaReintento,
    private val reloj: Reloj,
    private val alcance: CoroutineScope,
    private val tamanoLoteSubida: Int = 100,
    private val maxPaginasBajada: Int = 100,
) {
    private val mutex = Mutex()
    private val sincronizandoAhora = MutableStateFlow(false)

    /** Estado observable para el indicador del encabezado. */
    val estado: StateFlow<EstadoSincronizacion> =
        combine(
            sincronizacion.observarPendientes(),
            sincronizacion.observarConflictos(),
            sincronizacion.observarEstado(),
            sincronizandoAhora,
        ) { pendientes, conflictos, estadoRepo, sincronizando ->
            EstadoSincronizacion(
                pendientes = pendientes,
                conflictos = conflictos.size,
                ultimoExito = estadoRepo.ultimoExito,
                sincronizando = sincronizando,
            )
        }.stateIn(alcance, SharingStarted.Eagerly, EstadoSincronizacion())

    init {
        // Disparo automático al recuperar red (flanco de subida false -> true).
        conectividad.enLinea
            .distinctUntilChanged()
            .filter { enLinea -> enLinea }
            .onEach { sincronizarEnSegundoPlano() }
            .launchIn(alcance)
    }

    /** Lanza una sincronización sin bloquear al llamador (botón de UI, worker). */
    fun sincronizarEnSegundoPlano() {
        alcance.launch { sincronizar() }
    }

    /**
     * Ejecuta un ciclo completo. Reentrante-seguro: si ya hay uno en curso, sale.
     */
    suspend fun sincronizar(): Resultado<Unit> {
        // Si ya hay una sincronización en curso, esta llamada no hace nada.
        if (!mutex.tryLock()) return Resultado.Exito(Unit)

        sincronizandoAhora.value = true
        sincronizacion.marcarSincronizando(true)
        try {
            val ambito = sesion.sesionActual()?.ambito ?: Ambito.Global

            val subida = subirPendientes()
            if (subida is Resultado.Fallo && !subida.error.esReintentable) return subida
            // Si el fallo de subida es reintentable, seguimos con la bajada: el
            // outbox quedó con intentos+1 y se reintenta en la próxima pasada.

            val bajada = bajarDeltas(ambito)
            if (bajada is Resultado.Fallo) return bajada

            sincronizacion.registrarExito(reloj.ahora().toString())
            return Resultado.Exito(Unit)
        } finally {
            sincronizacion.marcarSincronizando(false)
            sincronizandoAhora.value = false
            mutex.unlock()
        }
    }

    // ------------------------------------------------------------------
    // SUBIDA
    // ------------------------------------------------------------------
    private suspend fun subirPendientes(): Resultado<Unit> {
        var pasada = 0
        while (pasada++ < maxPaginasBajada) {
            val lote = sincronizacion.siguienteLote(tamanoLoteSubida)
            if (lote.isEmpty()) return Resultado.Exito(Unit)

            when (val respuesta = cliente.subir(lote)) {
                is Resultado.Fallo -> {
                    // Fallo de transporte: cuenta como un intento para todo el lote
                    // (con backoff se espaciarán los reintentos).
                    lote.forEach { op ->
                        sincronizacion.registrarIntentoFallido(op.idLocal, respuesta.error.mensaje)
                        if (politica.agotado(op.intentos + 1)) {
                            sincronizacion.marcarConflicto(op.idLocal, "reintentos agotados: ${respuesta.error.mensaje}", null)
                        }
                    }
                    return respuesta
                }

                is Resultado.Exito -> {
                    val avanzo = procesarResultadoSubida(lote, respuesta.valor)
                    // Si una pasada no resolvió ninguna operación, cortamos para
                    // no entrar en bucle (quedaron marcadas como conflicto).
                    if (!avanzo) return Resultado.Exito(Unit)
                }
            }
        }
        return Resultado.Exito(Unit)
    }

    /** @return true si al menos una operación salió del outbox. */
    private suspend fun procesarResultadoSubida(lote: List<OperacionOutbox>, r: ResultadoSubida): Boolean {
        val porClave = lote.associateBy { it.tabla to it.idRegistro }
        var resueltas = 0

        r.aceptadas.forEach { a ->
            porClave[a.tabla to a.idRegistro]?.let {
                sincronizacion.marcarSincronizada(it.idLocal, a.version); resueltas++
            }
        }

        r.conflictos.forEach { c ->
            val op = porClave[c.tabla to c.idRegistro] ?: return@forEach
            when (op.estrategia) {
                // El servidor ya tiene un movimiento con este id: nada que hacer.
                EstrategiaConflicto.CONMUTATIVO -> {
                    sincronizacion.descartar(op.idLocal); resueltas++
                }
                // Gana el servidor: se descarta el cambio local; la bajada traerá la verdad.
                EstrategiaConflicto.SERVIDOR_GANA -> {
                    sincronizacion.descartar(op.idLocal); resueltas++
                }
                // Divergencia real que el usuario debe ver.
                EstrategiaConflicto.SOLO_INSERCION,
                EstrategiaConflicto.VERSION -> {
                    sincronizacion.marcarConflicto(op.idLocal, c.motivo, c.servidorJson); resueltas++
                }
            }
        }

        r.rechazadas.forEach { rc ->
            porClave[rc.tabla to rc.idRegistro]?.let {
                // Rechazo de validación/integridad/rol: es permanente.
                sincronizacion.marcarConflicto(it.idLocal, rc.motivo, null); resueltas++
            }
        }

        // Operaciones del lote de las que el servidor no dijo nada: intento fallido.
        val mencionadas = (r.aceptadas.map { it.tabla to it.idRegistro } +
            r.conflictos.map { it.tabla to it.idRegistro } +
            r.rechazadas.map { it.tabla to it.idRegistro }).toSet()
        lote.filter { (it.tabla to it.idRegistro) !in mencionadas }.forEach {
            sincronizacion.registrarIntentoFallido(it.idLocal, "sin respuesta del servidor")
            if (politica.agotado(it.intentos + 1)) {
                sincronizacion.marcarConflicto(it.idLocal, "reintentos agotados", null)
                resueltas++
            }
        }

        return resueltas > 0
    }

    // ------------------------------------------------------------------
    // BAJADA
    // ------------------------------------------------------------------
    private suspend fun bajarDeltas(ambito: Ambito): Resultado<Unit> {
        var pagina = 0
        while (pagina++ < maxPaginasBajada) {
            val desde = sincronizacion.cursor(ambito)
            when (val r = cliente.bajar(desde, ambito)) {
                is Resultado.Fallo -> return r
                is Resultado.Exito -> {
                    sincronizacion.guardarCursor(ambito, r.valor.cursor)
                    if (!r.valor.hayMas) return Resultado.Exito(Unit)
                }
            }
        }
        return Resultado.Exito(Unit)
    }

    /**
     * Reintento diferido de UNA operación tras un fallo, respetando el backoff.
     * Lo usa el worker en segundo plano para no martillar el backend.
     */
    suspend fun esperarBackoff(intentos: Int) {
        delay(politica.demoraPara(intentos))
    }
}
