package pe.edu.upeu.milkflow.data.local.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import app.cash.sqldelight.coroutines.mapToOneOrNull
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlinx.datetime.TimeZone
import kotlinx.datetime.todayIn
import kotlin.time.Clock
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.data.local.ConstructorPayload
import pe.edu.upeu.milkflow.data.local.aDominio
import pe.edu.upeu.milkflow.data.local.aLong
import pe.edu.upeu.milkflow.data.local.aMillis
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.Inspeccion
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Recepcion
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.model.SolicitudRuta
import pe.edu.upeu.milkflow.domain.repository.AvisoRepository
import pe.edu.upeu.milkflow.domain.repository.InspeccionRepository
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.RecepcionRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.repository.SolicitudRutaRepository
import pe.edu.upeu.milkflow.domain.sync.Entidades
import pe.edu.upeu.milkflow.domain.vo.Litros
import pe.edu.upeu.milkflow.domain.model.Aviso

/*
 * Repositorios de escritura. PATRÓN INVARIANTE:
 *   db.transaction {
 *       <escritura de negocio en su tabla>
 *       q.encolar(<operación de outbox>)      // MISMA transacción
 *   }
 * Si algo falla, ni la fila de negocio ni la del outbox quedan a medias.
 * Ninguna llamada a red. El SyncManager drena el outbox después.
 */

private const val OP_INSERTAR = "INSERTAR"
private const val OP_ACTUALIZAR = "ACTUALIZAR"

class RecoleccionRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : RecoleccionRepository {
    private val q get() = db.milkFlowQueries

    override fun observarPorJornada(jornadaId: String): Flow<List<Recoleccion>> =
        q.recoleccionesPorJornada(jornadaId).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }

    override fun observarTodas(): Flow<List<Recoleccion>> =
        q.recoleccionesTodas().asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }

    override fun observarPorProductor(productorId: String): Flow<List<Recoleccion>> =
        q.recoleccionesPorProductor(productorId).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }

    override suspend fun yaRegistrada(jornadaId: String, productorId: String): Boolean = withContext(io) {
        q.recoleccionExiste(jornadaId, productorId).executeAsOne() > 0L
    }

    override suspend fun totalLitros(jornadaId: String): Litros = withContext(io) {
        Litros.confiar(q.totalLitrosJornada(jornadaId).executeAsOne())
    }

    override suspend fun registrar(recoleccion: Recoleccion): Resultado<Recoleccion> = withContext(io) {
        runCatching {
            db.transaction {
                q.insertRecoleccion(
                    recoleccion.id, recoleccion.jornadaId, recoleccion.productorId,
                    recoleccion.litros.valor, recoleccion.horaRegistro.aMillis(),
                    recoleccion.observacion, recoleccion.sospechaAdulteracion.aLong(),
                    recoleccion.updatedAt.aMillis(), recoleccion.version, recoleccion.deleted.aLong(),
                    "PENDIENTE", 0L, null,
                )
                q.encolar(
                    OP_INSERTAR, Entidades.RECOLECCION, recoleccion.id,
                    ConstructorPayload.recoleccion(recoleccion), recoleccion.version, recoleccion.updatedAt.aMillis(),
                )
            }
        }.fold(
            onSuccess = { Resultado.Exito(recoleccion) },
            onFailure = { Resultado.Fallo(ErrorApp.ErrorLocal(it)) },
        )
    }
}

class RecepcionRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : RecepcionRepository {
    private val q get() = db.milkFlowQueries

    override fun observarPorJornada(jornadaId: String): Flow<Recepcion?> =
        q.recepcionPorJornada(jornadaId).asFlow().mapToOneOrNull(io).map { it?.aDominio() }

    override suspend fun registrar(recepcion: Recepcion): Resultado<Recepcion> = withContext(io) {
        runCatching {
            db.transaction {
                q.insertRecepcion(
                    recepcion.id, recepcion.jornadaId, recepcion.tina, recepcion.litrosDescargados.valor,
                    recepcion.horaDescarga.aMillis(), recepcion.recibidoPor,
                    recepcion.updatedAt.aMillis(), recepcion.version, recepcion.deleted.aLong(),
                    "PENDIENTE", 0L, null,
                )
                q.encolar(
                    OP_INSERTAR, Entidades.RECEPCION, recepcion.id,
                    ConstructorPayload.recepcion(recepcion), recepcion.version, recepcion.updatedAt.aMillis(),
                )
            }
        }.fold({ Resultado.Exito(recepcion) }, { Resultado.Fallo(ErrorApp.ErrorLocal(it)) })
    }
}

class JornadaRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
    private val recepciones: RecepcionRepository,
    private val generadorId: GeneradorId,
    private val reloj: Reloj,
) : JornadaRepository {
    private val q get() = db.milkFlowQueries

    // `acopiadorId` se ignora: la BD local solo tiene jornadas del acopiador
    // logueado (ver comentario en la query `jornadaActiva`).
    override fun observarJornadaActiva(acopiadorId: String): Flow<JornadaRuta?> =
        q.jornadaActiva().asFlow().mapToOneOrNull(io).map { it?.aDominio() }

    override suspend fun jornadaActiva(acopiadorId: String): JornadaRuta? = withContext(io) {
        q.jornadaActiva().executeAsOneOrNull()?.aDominio()
    }

    override suspend fun porId(id: String): JornadaRuta? = withContext(io) {
        q.jornadaPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun iniciar(acopiadorId: String, rutaId: String): Resultado<JornadaRuta> = withContext(io) {
        val ahora = reloj.ahora()
        val jornada = JornadaRuta(
            id = generadorId.nuevo(),
            acopiadorId = acopiadorId,
            rutaId = rutaId,
            dispositivoId = null,
            fecha = Clock.System.todayIn(TimeZone.of("America/Lima")),
            horaInicio = ahora,
            horaCierre = null,
            litrosDeclarados = Litros.CERO,
            estado = EstadoJornada.EN_CURSO,
            updatedAt = ahora,
            version = 0,
            deleted = false,
        )
        runCatching {
            db.transaction {
                q.insertJornada(
                    jornada.id, jornada.acopiadorId, jornada.rutaId, jornada.dispositivoId,
                    jornada.fecha.toString(), jornada.horaInicio.aMillis(), null, 0.0,
                    jornada.estado.clave, jornada.updatedAt.aMillis(), jornada.version, 0L,
                    "PENDIENTE", 0L, null,
                )
                q.encolar(
                    OP_INSERTAR, Entidades.JORNADA, jornada.id,
                    ConstructorPayload.jornada(jornada), jornada.version, jornada.updatedAt.aMillis(),
                )
            }
        }.fold({ Resultado.Exito(jornada) }, { Resultado.Fallo(ErrorApp.ErrorLocal(it)) })
    }

    override suspend fun cerrar(jornada: JornadaRuta, recepcion: Recepcion?): Resultado<JornadaRuta> = withContext(io) {
        runCatching {
            db.transaction {
                q.actualizarJornada(
                    jornada.horaCierre?.aMillis(), jornada.litrosDeclarados.valor, jornada.estado.clave,
                    jornada.updatedAt.aMillis(), jornada.version, jornada.deleted.aLong(), "PENDIENTE", jornada.id,
                )
                // La cabecera es concurrencia optimista por versión: ACTUALIZAR.
                q.encolar(
                    OP_ACTUALIZAR, Entidades.JORNADA, jornada.id,
                    ConstructorPayload.jornada(jornada), jornada.version, jornada.updatedAt.aMillis(),
                )
                if (recepcion != null) {
                    q.insertRecepcion(
                        recepcion.id, recepcion.jornadaId, recepcion.tina, recepcion.litrosDescargados.valor,
                        recepcion.horaDescarga.aMillis(), recepcion.recibidoPor,
                        recepcion.updatedAt.aMillis(), recepcion.version, recepcion.deleted.aLong(),
                        "PENDIENTE", 0L, null,
                    )
                    q.encolar(
                        OP_INSERTAR, Entidades.RECEPCION, recepcion.id,
                        ConstructorPayload.recepcion(recepcion), recepcion.version, recepcion.updatedAt.aMillis(),
                    )
                }
            }
        }.fold({ Resultado.Exito(jornada) }, { Resultado.Fallo(ErrorApp.ErrorLocal(it)) })
    }
}

class InspeccionRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : InspeccionRepository {
    private val q get() = db.milkFlowQueries

    override fun observarPorProductor(productorId: String): Flow<List<Inspeccion>> =
        q.inspeccionesPorProductor(productorId).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }

    override suspend fun registrar(inspeccion: Inspeccion): Resultado<Inspeccion> = withContext(io) {
        runCatching {
            db.transaction {
                q.insertInspeccion(
                    inspeccion.id, inspeccion.productorId, inspeccion.supervisorId,
                    inspeccion.jornadaId, inspeccion.recoleccionId, inspeccion.tomadoEn.aMillis(),
                    inspeccion.medicion.aguaAnadidaPorcentaje, inspeccion.medicion.ph,
                    inspeccion.medicion.densidad, inspeccion.medicion.grasaPorcentaje,
                    inspeccion.medicion.solidosNoGrasosPorcentaje, inspeccion.medicion.temperatura,
                    inspeccion.dictamen.clave, inspeccion.dictamenDetalle, inspeccion.esReincidencia.aLong(),
                    inspeccion.updatedAt.aMillis(), inspeccion.version, inspeccion.deleted.aLong(),
                    "PENDIENTE", 0L, null,
                )
                q.encolar(
                    OP_INSERTAR, Entidades.INSPECCION, inspeccion.id,
                    ConstructorPayload.inspeccion(inspeccion), inspeccion.version, inspeccion.updatedAt.aMillis(),
                )
            }
        }.fold({ Resultado.Exito(inspeccion) }, { Resultado.Fallo(ErrorApp.ErrorLocal(it)) })
    }
}

class SolicitudRutaRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : SolicitudRutaRepository {
    private val q get() = db.milkFlowQueries

    override fun observarMisSolicitudes(productorId: String): Flow<List<SolicitudRuta>> =
        q.solicitudesPorProductor(productorId).asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }

    override suspend fun crear(solicitud: SolicitudRuta): Resultado<SolicitudRuta> = withContext(io) {
        runCatching {
            db.transaction {
                q.insertSolicitudRuta(
                    solicitud.id, solicitud.productorId, solicitud.zonaActualId, solicitud.zonaSolicitadaId,
                    solicitud.motivo, solicitud.estado.clave, solicitud.resueltoEn?.aMillis(),
                    solicitud.comentarioResolucion, solicitud.updatedAt.aMillis(), solicitud.version,
                    solicitud.deleted.aLong(), "PENDIENTE", 0L, null,
                )
                q.encolar(
                    OP_INSERTAR, Entidades.SOLICITUD_RUTA, solicitud.id,
                    ConstructorPayload.solicitudRuta(solicitud), solicitud.version, solicitud.updatedAt.aMillis(),
                )
            }
        }.fold({ Resultado.Exito(solicitud) }, { Resultado.Fallo(ErrorApp.ErrorLocal(it)) })
    }
}

class AvisoRepositoryLocal(
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
    private val generadorId: GeneradorId,
    private val reloj: Reloj,
    // Id del usuario en sesión: solo se usa para deduplicar el acuse en local
    // (el backend deriva el productor del token).
    private val sesionUsuarioId: suspend () -> String?,
) : AvisoRepository {
    private val q get() = db.milkFlowQueries

    override fun observarActivos(): Flow<List<Aviso>> =
        q.avisosActivos().asFlow().mapToList(io).map { l -> l.map { it.aDominio() } }

    override suspend fun marcarVisto(avisoId: String): Resultado<Unit> = withContext(io) {
        val productorId = sesionUsuarioId()
            ?: return@withContext Resultado.Fallo(ErrorApp.Validacion("Sesión", "no hay usuario en sesión"))
        val ahora = reloj.ahora()
        runCatching {
            db.transaction {
                q.marcarAvisoVistoLocal(avisoId)                 // oculta el pop-up de inmediato
                val idVisto = generadorId.nuevo()
                q.insertAvisoVisto(idVisto, avisoId, productorId, ahora.aMillis(), ahora.aMillis())
                q.encolar(
                    OP_INSERTAR, Entidades.AVISO_VISTO, avisoId,
                    ConstructorPayload.avisoVisto(avisoId), 0L, ahora.aMillis(),
                )
            }
        }.fold({ Resultado.Exito(Unit) }, { Resultado.Fallo(ErrorApp.ErrorLocal(it)) })
    }
}
