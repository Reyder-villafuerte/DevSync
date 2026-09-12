package pe.edu.upeu.milkflow.ui

import kotlin.time.Instant
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.map
import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.Aviso
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.EstadoProductor
import pe.edu.upeu.milkflow.domain.model.Inspeccion
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Liquidacion
import pe.edu.upeu.milkflow.domain.model.Precio
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Ruta
import pe.edu.upeu.milkflow.domain.model.Sancion
import pe.edu.upeu.milkflow.domain.model.SolicitudRuta
import pe.edu.upeu.milkflow.domain.model.Zona
import pe.edu.upeu.milkflow.domain.repository.AvisoRepository
import pe.edu.upeu.milkflow.domain.repository.InspeccionRepository
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.LiquidacionRepository
import pe.edu.upeu.milkflow.domain.repository.PrecioRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SesionRepository
import pe.edu.upeu.milkflow.domain.repository.SolicitudRutaRepository
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.flow.MutableSharedFlow
import pe.edu.upeu.milkflow.domain.sync.ClienteSincronizacion
import pe.edu.upeu.milkflow.domain.sync.ObservadorConectividad
import pe.edu.upeu.milkflow.domain.sync.OperacionOutbox
import pe.edu.upeu.milkflow.domain.sync.PoliticaReintento
import pe.edu.upeu.milkflow.domain.sync.ResultadoSubida
import pe.edu.upeu.milkflow.domain.sync.ResumenBajada
import pe.edu.upeu.milkflow.domain.sync.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.sync.SyncManager
import pe.edu.upeu.milkflow.domain.sync.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.vo.Dni
import pe.edu.upeu.milkflow.domain.vo.Litros

val T0: Instant = Instant.parse("2026-09-10T12:00:00Z")
val HOY: LocalDate = LocalDate.parse("2026-09-10")

val RelojFijo = Reloj { T0 }
val IdSecuencial = object : GeneradorId {
    private var n = 0
    override fun nuevo(): String = "id-${++n}"
}

fun productor(
    id: String,
    zonaId: String,
    nombres: String = "Prod",
    apellidos: String = id,
    estado: EstadoProductor = EstadoProductor.ACTIVO,
) = Productor(
    id = id, codigoPadron = "P-$id", nombres = nombres, apellidos = apellidos,
    dni = Dni.confiar("70000000"), zonaId = zonaId, telefono = null, estado = estado,
    fechaIngreso = HOY, updatedAt = T0, version = 1,
)

fun zona(id: String, rutaId: String, nombre: String = id) =
    Zona(id = id, nombre = nombre, codigo = id, rutaId = rutaId, activa = true, updatedAt = T0, version = 1)

fun ruta(id: String, codigo: String, nombre: String = "Ruta $codigo") =
    Ruta(id = id, nombre = nombre, codigo = codigo, activa = true, updatedAt = T0, version = 1)

fun jornada(id: String, acopiadorId: String, rutaId: String, estado: EstadoJornada = EstadoJornada.EN_CURSO) =
    JornadaRuta(
        id = id, acopiadorId = acopiadorId, rutaId = rutaId, dispositivoId = null,
        fecha = HOY, horaInicio = T0, horaCierre = null, litrosDeclarados = Litros.CERO,
        estado = estado, updatedAt = T0, version = 1,
    )

fun recoleccion(id: String, jornadaId: String, productorId: String, litros: Double, cuando: Instant) =
    Recoleccion(
        id = id, jornadaId = jornadaId, productorId = productorId, litros = Litros.confiar(litros),
        horaRegistro = cuando, observacion = null, sospechaAdulteracion = false,
        updatedAt = cuando, version = 1,
    )

fun avisoObligatorio(id: String, titulo: String = "Asamblea", visto: Boolean = false) = Aviso(
    id = id, titulo = titulo, contenido = "Contenido del aviso", imagenUrl = null, obligatorio = true,
    fechaPublicacion = T0 - kotlin.time.Duration.parse("PT24H"), fechaExpiracion = null,
    vistoLocalmente = visto, updatedAt = T0, version = 1,
)

fun precioCompra(valor: Double) = Precio(
    id = "precio-compra", concepto = pe.edu.upeu.milkflow.domain.model.ConceptoPrecio.COMPRA_LECHE,
    productoId = null, tipoCliente = null, valor = valor, valorMinimo = 0.65,
    vigenteDesde = LocalDate.parse("2026-01-01"), vigenteHasta = null, updatedAt = T0, version = 1,
)

fun sesion(rol: RolUsuario, ambito: Ambito, usuarioId: String = "u1") =
    SesionActiva(usuarioId = usuarioId, nombreCompleto = "Usuario Prueba", rol = rol, ambito = ambito, dispositivoId = "disp1")

// ---------------------------------------------------------------------------

class FakeSesionRepository(
    var resultadoLogin: Resultado<SesionActiva> = Resultado.Fallo(ErrorApp.NoAutorizado),
) : SesionRepository {
    val sesion = MutableStateFlow<SesionActiva?>(null)
    override fun observarSesion(): Flow<SesionActiva?> = sesion
    override suspend fun sesionActual(): SesionActiva? = sesion.value
    override suspend fun iniciarSesion(dni: Dni, password: String, identificadorDispositivo: String): Resultado<SesionActiva> {
        (resultadoLogin as? Resultado.Exito)?.let { sesion.value = it.valor }
        return resultadoLogin
    }
    override suspend fun cerrarSesion(): Resultado<Unit> { sesion.value = null; return Resultado.Exito(Unit) }
    override suspend fun tokenActual(): String? = null
}

class FakeRutaRepository(rutas: List<Ruta>) : RutaRepository {
    val flujo = MutableStateFlow(rutas)
    override fun observarTodas(): Flow<List<Ruta>> = flujo
    override suspend fun porId(id: String): Ruta? = flujo.value.firstOrNull { it.id == id }
}

class FakeZonaRepository(zonas: List<Zona>) : ZonaRepository {
    val flujo = MutableStateFlow(zonas)
    override fun observarTodas(): Flow<List<Zona>> = flujo
    override suspend fun porId(id: String): Zona? = flujo.value.firstOrNull { it.id == id }
}

class FakeProductorRepository(productores: List<Productor>) : ProductorRepository {
    val flujo = MutableStateFlow(productores)
    override fun observarPorZona(zonaId: String): Flow<List<Productor>> = flujo.map { it.filter { p -> p.zonaId == zonaId } }
    override fun observarPadron(): Flow<List<Productor>> = flujo.map { it.filter { p -> p.enPadron } }
    override suspend fun porId(id: String): Productor? = flujo.value.firstOrNull { it.id == id }
}

class FakeJornadaRepository(inicial: JornadaRuta? = null) : JornadaRepository {
    val flujo = MutableStateFlow(inicial)
    /** Jornada de hoy ya cerrada/conciliada, para probar el bloqueo de duplicados. */
    var deHoy: JornadaRuta? = null
    override fun observarJornadaActiva(acopiadorId: String): Flow<JornadaRuta?> = flujo
    override suspend fun jornadaActiva(acopiadorId: String): JornadaRuta? = flujo.value
    override suspend fun porId(id: String): JornadaRuta? = flujo.value?.takeIf { it.id == id }
    override suspend fun jornadaDeHoy(): JornadaRuta? = flujo.value ?: deHoy
    override fun observarTodas(): Flow<List<JornadaRuta>> = flujo.map { listOfNotNull(it) }
    override suspend fun iniciar(acopiadorId: String, rutaId: String): Resultado<JornadaRuta> {
        val j = jornada("j-new", acopiadorId, rutaId)
        flujo.value = j
        return Resultado.Exito(j)
    }
    override suspend fun cerrar(jornada: JornadaRuta): Resultado<JornadaRuta> {
        flujo.value = jornada
        return Resultado.Exito(jornada)
    }
}

class FakeRecoleccionRepository : RecoleccionRepository {
    val flujo = MutableStateFlow<List<Recoleccion>>(emptyList())
    override fun observarPorJornada(jornadaId: String): Flow<List<Recoleccion>> = flujo.map { it.filter { r -> r.jornadaId == jornadaId } }
    override fun observarTodas(): Flow<List<Recoleccion>> = flujo
    override fun observarPorProductor(productorId: String): Flow<List<Recoleccion>> =
        flujo.map { l -> l.filter { r -> r.productorId == productorId }.sortedByDescending { it.horaRegistro } }
    override suspend fun registrar(recoleccion: Recoleccion): Resultado<Recoleccion> {
        flujo.value = flujo.value + recoleccion
        return Resultado.Exito(recoleccion)
    }
    override suspend fun yaRegistrada(jornadaId: String, productorId: String): Boolean =
        flujo.value.any { it.jornadaId == jornadaId && it.productorId == productorId }
    override suspend fun totalLitros(jornadaId: String): Litros =
        Litros.sumar(flujo.value.filter { it.jornadaId == jornadaId }.map { it.litros })
}

class FakeInspeccionRepository : InspeccionRepository {
    val flujo = MutableStateFlow<List<Inspeccion>>(emptyList())
    override fun observarPorProductor(productorId: String): Flow<List<Inspeccion>> = flujo.map { it.filter { i -> i.productorId == productorId } }
    override suspend fun registrar(inspeccion: Inspeccion): Resultado<Inspeccion> {
        flujo.value = flujo.value + inspeccion
        return Resultado.Exito(inspeccion)
    }
}

class FakeSancionRepository(var aguaVigente: Boolean = false) : SancionRepository {
    val flujo = MutableStateFlow<List<Sancion>>(emptyList())
    override fun observarPorProductor(productorId: String): Flow<List<Sancion>> = flujo
    override suspend fun tieneSancionAguaVigente(productorId: String): Boolean = aguaVigente
}

class FakeAvisoRepository(inicial: List<Aviso> = emptyList()) : AvisoRepository {
    val flujo = MutableStateFlow(inicial)
    override fun observarActivos(): Flow<List<Aviso>> = flujo
    override suspend fun marcarVisto(avisoId: String): Resultado<Unit> {
        flujo.value = flujo.value.map { if (it.id == avisoId) it.copy(vistoLocalmente = true) else it }
        return Resultado.Exito(Unit)
    }
}

class FakePrecioRepository(precios: List<Precio> = emptyList()) : PrecioRepository {
    val flujo = MutableStateFlow(precios)
    override fun observarVigentes(): Flow<List<Precio>> = flujo
    override suspend fun tarifaCompraVigente(): Precio? = flujo.value.firstOrNull()
}

class FakeLiquidacionRepository(liqs: List<Liquidacion> = emptyList()) : LiquidacionRepository {
    val flujo = MutableStateFlow(liqs)
    override fun observarPorProductor(productorId: String): Flow<List<Liquidacion>> = flujo
}

class FakeSolicitudRutaRepository : SolicitudRutaRepository {
    val flujo = MutableStateFlow<List<SolicitudRuta>>(emptyList())
    override fun observarMisSolicitudes(productorId: String): Flow<List<SolicitudRuta>> = flujo
    override suspend fun crear(solicitud: SolicitudRuta): Resultado<SolicitudRuta> {
        flujo.value = flujo.value + solicitud
        return Resultado.Exito(solicitud)
    }
}

class FakeSincronizacionRepository : SincronizacionRepository {
    val pendientes = MutableStateFlow(0)
    val conflictos = MutableStateFlow<List<OperacionOutbox>>(emptyList())
    val estadoSync = MutableStateFlow(EstadoSincronizacion())
    var vecesSincronizado = 0
    override suspend fun siguienteLote(limite: Int): List<OperacionOutbox> = emptyList()
    override suspend fun marcarSincronizada(idLocal: Long, versionServidor: Long) {}
    override suspend fun registrarIntentoFallido(idLocal: Long, error: String) {}
    override suspend fun marcarConflicto(idLocal: Long, motivo: String, servidorJson: String?) {}
    override suspend fun descartar(idLocal: Long) {}
    override fun observarPendientes(): Flow<Int> = pendientes
    override fun observarConflictos(): Flow<List<OperacionOutbox>> = conflictos
    override suspend fun cursor(ambito: Ambito): String? = null
    override suspend fun guardarCursor(ambito: Ambito, cursor: String) {}
    override fun observarEstado(): Flow<EstadoSincronizacion> = estadoSync
    override suspend fun marcarSincronizando(activo: Boolean) {}
    override suspend fun registrarExito(instanteIso: String) { vecesSincronizado++ }
}

class FakeClienteSincronizacion : ClienteSincronizacion {
    override suspend fun subir(operaciones: List<OperacionOutbox>): Resultado<ResultadoSubida> =
        Resultado.Exito(ResultadoSubida(emptyList(), emptyList(), emptyList()))
    override suspend fun bajar(desde: String?, ambito: Ambito): Resultado<ResumenBajada> =
        Resultado.Exito(ResumenBajada(cursor = "c", hayMas = false, servidorEn = T0, filasAplicadas = 0))
}

class FakeConectividad : ObservadorConectividad {
    override val enLinea: MutableSharedFlow<Boolean> = MutableSharedFlow(replay = 1)
}

fun syncManagerDePrueba(
    scope: CoroutineScope,
    sincronizacion: SincronizacionRepository,
    sesiones: SesionRepository = FakeSesionRepository(),
): SyncManager = SyncManager(
    sincronizacion = sincronizacion,
    cliente = FakeClienteSincronizacion(),
    sesion = sesiones,
    conectividad = FakeConectividad(),
    politica = PoliticaReintento(),
    reloj = RelojFijo,
    alcance = scope,
)
