package pe.edu.upeu.milkflow.fakes

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.map
import kotlin.time.Instant
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SesionRepository
import pe.edu.upeu.milkflow.domain.sync.ClienteSincronizacion
import pe.edu.upeu.milkflow.domain.sync.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.sync.ObservadorConectividad
import pe.edu.upeu.milkflow.domain.sync.OperacionOutbox
import pe.edu.upeu.milkflow.domain.sync.ResultadoSubida
import pe.edu.upeu.milkflow.domain.sync.ResumenBajada
import pe.edu.upeu.milkflow.domain.sync.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.sync.TipoOperacion
import pe.edu.upeu.milkflow.domain.vo.Dni
import pe.edu.upeu.milkflow.domain.vo.Litros

/** Reloj fijo para tests deterministas. */
class RelojFijo(var instante: Instant = Instant.parse("2026-09-09T10:00:00Z")) : Reloj {
    override fun ahora(): Instant = instante
}

/** GeneradorId secuencial y predecible. */
class GeneradorIdSecuencial : pe.edu.upeu.milkflow.core.GeneradorId {
    private var n = 0
    override fun nuevo(): String = "id-${++n}"
}

class ConectividadFake(inicial: Boolean = false) : ObservadorConectividad {
    private val estado = MutableStateFlow(inicial)
    override val enLinea: Flow<Boolean> = estado.asStateFlow()
    fun emitir(valor: Boolean) { estado.value = valor }
}

/**
 * Outbox en memoria que replica el `ON CONFLICT(tabla, id_registro) DO UPDATE`
 * del esquema SQLDelight: una sola operación pendiente por registro.
 */
class SincronizacionRepositoryFake : SincronizacionRepository {
    private var secuencia = 0L
    val filas = mutableListOf<OperacionOutbox>()
    private val pendientes = MutableStateFlow(0)
    private val conflictos = MutableStateFlow<List<OperacionOutbox>>(emptyList())
    private val estado = MutableStateFlow(EstadoSincronizacion())
    val cursores = mutableMapOf<String, String>()
    var sincronizandoMarcado = false
    var ultimoExitoIso: String? = null

    fun encolar(op: TipoOperacion, tabla: String, idRegistro: String, payload: String, versionBase: Long) {
        val existente = filas.indexOfFirst { it.tabla == tabla && it.idRegistro == idRegistro }
        val nueva = OperacionOutbox(
            idLocal = if (existente >= 0) filas[existente].idLocal else ++secuencia,
            operacion = op, tabla = tabla, idRegistro = idRegistro, payloadJson = payload,
            versionBase = versionBase, intentos = 0, ultimoError = null,
            creadoEn = Instant.parse("2026-09-09T10:00:00Z"),
        )
        if (existente >= 0) filas[existente] = nueva else filas.add(nueva)
        recomputar()
    }

    private fun recomputar() {
        conflictos.value = filas.filter { it.ultimoError == "CONFLICTO" }
        pendientes.value = filas.count { it.ultimoError != "CONFLICTO" }
    }

    override suspend fun siguienteLote(limite: Int): List<OperacionOutbox> =
        filas.filter { it.ultimoError != "CONFLICTO" }.take(limite)

    override suspend fun marcarSincronizada(idLocal: Long, versionServidor: Long) {
        filas.removeAll { it.idLocal == idLocal }; recomputar()
    }

    override suspend fun registrarIntentoFallido(idLocal: Long, error: String) {
        val i = filas.indexOfFirst { it.idLocal == idLocal }
        if (i >= 0) filas[i] = filas[i].copy(intentos = filas[i].intentos + 1, ultimoError = error)
        recomputar()
    }

    override suspend fun marcarConflicto(idLocal: Long, motivo: String, servidorJson: String?) {
        val i = filas.indexOfFirst { it.idLocal == idLocal }
        if (i >= 0) filas[i] = filas[i].copy(ultimoError = "CONFLICTO")
        recomputar()
    }

    override suspend fun descartar(idLocal: Long) { filas.removeAll { it.idLocal == idLocal }; recomputar() }

    override fun observarPendientes(): Flow<Int> = pendientes.asStateFlow()
    override fun observarConflictos(): Flow<List<OperacionOutbox>> = conflictos.asStateFlow()

    override suspend fun cursor(ambito: Ambito): String? = cursores[ambito.parametro]
    override suspend fun guardarCursor(ambito: Ambito, cursor: String) { cursores[ambito.parametro] = cursor }

    override fun observarEstado(): Flow<EstadoSincronizacion> = estado.asStateFlow()
    override suspend fun marcarSincronizando(activo: Boolean) { sincronizandoMarcado = activo }
    override suspend fun registrarExito(instanteIso: String) {
        ultimoExitoIso = instanteIso
        estado.value = estado.value.copy(ultimoExito = Instant.parse(instanteIso))
    }
}

/** Cliente de sincronización guionizado. */
class ClienteSincronizacionFake : ClienteSincronizacion {
    var respuestaSubida: (List<OperacionOutbox>) -> Resultado<ResultadoSubida> =
        { Resultado.Exito(ResultadoSubida(emptyList(), emptyList(), emptyList())) }
    var respuestaBajada: (String?, Ambito) -> Resultado<ResumenBajada> =
        { _, _ -> Resultado.Exito(ResumenBajada("cursor-1", false, Instant.parse("2026-09-09T10:05:00Z"), 0)) }
    val lotesSubidos = mutableListOf<List<OperacionOutbox>>()

    override suspend fun subir(operaciones: List<OperacionOutbox>): Resultado<ResultadoSubida> {
        lotesSubidos.add(operaciones)
        return respuestaSubida(operaciones)
    }

    override suspend fun bajar(desde: String?, ambito: Ambito): Resultado<ResumenBajada> = respuestaBajada(desde, ambito)
}

class SesionRepositoryFake(
    private val sesion: SesionActiva? = SesionActiva("u1", "Test", pe.edu.upeu.milkflow.domain.model.RolUsuario.ACOPIADOR, Ambito.Ruta("01"), "d1"),
) : SesionRepository {
    override fun observarSesion(): Flow<SesionActiva?> = MutableStateFlow(sesion)
    override suspend fun sesionActual(): SesionActiva? = sesion
    override suspend fun iniciarSesion(dni: Dni, password: String, identificadorDispositivo: String) =
        sesion?.let { Resultado.Exito(it) } ?: Resultado.Fallo(pe.edu.upeu.milkflow.core.ErrorApp.NoAutorizado)
    override suspend fun cerrarSesion() = Resultado.Exito(Unit)
    override suspend fun tokenActual(): String? = "token-fake"
}

class ProductorRepositoryFake(productores: List<Productor> = emptyList()) : ProductorRepository {
    val mapa = productores.associateBy { it.id }.toMutableMap()
    override fun observarPorZona(zonaId: String) = MutableStateFlow(mapa.values.filter { it.zonaId == zonaId })
    override fun observarPadron() = MutableStateFlow(mapa.values.filter { it.enPadron })
    override suspend fun porId(id: String): Productor? = mapa[id]
}

class SancionRepositoryFake(var conSancionAgua: Boolean = false) : SancionRepository {
    override fun observarPorProductor(productorId: String) = MutableStateFlow(emptyList<pe.edu.upeu.milkflow.domain.model.Sancion>())
    override suspend fun tieneSancionAguaVigente(productorId: String): Boolean = conSancionAgua
}

class RecoleccionRepositoryFake : RecoleccionRepository {
    val registradas = mutableListOf<Recoleccion>()
    val outbox = mutableListOf<Pair<String, String>>() // tabla to idRegistro
    override fun observarPorJornada(jornadaId: String) = MutableStateFlow(registradas.filter { it.jornadaId == jornadaId })
    override fun observarTodas() = MutableStateFlow(registradas.toList())
    override fun observarPorProductor(productorId: String) =
        MutableStateFlow(registradas.filter { it.productorId == productorId }.sortedByDescending { it.horaRegistro })
    override suspend fun registrar(recoleccion: Recoleccion): Resultado<Recoleccion> {
        registradas.add(recoleccion)
        outbox.add("registros_acopio" to recoleccion.id) // simula el encolar transaccional
        return Resultado.Exito(recoleccion)
    }
    override suspend fun yaRegistrada(jornadaId: String, productorId: String): Boolean =
        registradas.any { it.jornadaId == jornadaId && it.productorId == productorId }
    override suspend fun totalLitros(jornadaId: String): Litros =
        Litros.sumar(registradas.filter { it.jornadaId == jornadaId }.map { it.litros })
}

class JornadaRepositoryFake(var activa: JornadaRuta? = null, var deHoy: JornadaRuta? = null) : JornadaRepository {
    override fun observarJornadaActiva(acopiadorId: String) = MutableStateFlow(activa)
    override suspend fun jornadaActiva(acopiadorId: String): JornadaRuta? = activa
    override suspend fun porId(id: String): JornadaRuta? = activa?.takeIf { it.id == id }
    override suspend fun jornadaDeHoy(): JornadaRuta? = activa ?: deHoy
    override fun observarTodas() = MutableStateFlow(listOfNotNull(activa))
    override suspend fun iniciar(acopiadorId: String, rutaId: String): Resultado<JornadaRuta> {
        val j = JornadaRuta("j1", acopiadorId, rutaId, null, kotlinx.datetime.LocalDate(2026, 9, 9),
            Instant.parse("2026-09-09T05:00:00Z"), null, Litros.CERO, EstadoJornada.EN_CURSO,
            Instant.parse("2026-09-09T05:00:00Z"), 0, false)
        activa = j
        return Resultado.Exito(j)
    }
    override suspend fun cerrar(jornada: JornadaRuta): Resultado<JornadaRuta> {
        activa = jornada; return Resultado.Exito(jornada)
    }
}
