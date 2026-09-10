package pe.edu.upeu.milkflow.data.remote

import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToOneOrNull
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.remote.dto.DispositivoDto
import pe.edu.upeu.milkflow.data.remote.dto.LoginPeticionDto
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SesionRepository
import pe.edu.upeu.milkflow.domain.vo.Dni
import pe.edu.upeu.milkflow.infra.almacen.AlmacenSeguroToken

class SesionRepositoryHttp(
    private val api: ApiMilkFlow,
    private val almacen: AlmacenSeguroToken,
    private val db: MilkFlowDatabase,
    private val io: CoroutineDispatcher,
) : SesionRepository {

    private val q get() = db.milkFlowQueries

    override fun observarSesion(): Flow<SesionActiva?> =
        q.sesionActual().asFlow().mapToOneOrNull(io).map { fila ->
            fila?.let {
                SesionActiva(
                    usuarioId = it.usuario_id,
                    nombreCompleto = it.nombre,
                    rol = RolUsuario.desde(it.rol) ?: RolUsuario.PRODUCTOR,
                    ambito = Ambito.desde(it.ambito),
                    dispositivoId = it.dispositivo_id,
                )
            }
        }

    override suspend fun sesionActual(): SesionActiva? = withContext(io) {
        q.sesionActual().executeAsOneOrNull()?.let {
            SesionActiva(
                usuarioId = it.usuario_id,
                nombreCompleto = it.nombre,
                rol = RolUsuario.desde(it.rol) ?: RolUsuario.PRODUCTOR,
                ambito = Ambito.desde(it.ambito),
                dispositivoId = it.dispositivo_id,
            )
        }
    }

    override suspend fun iniciarSesion(
        dni: Dni,
        password: String,
        identificadorDispositivo: String,
    ): Resultado<SesionActiva> {
        val peticion = LoginPeticionDto(
            dni = dni.valor,
            password = password,
            dispositivo = DispositivoDto(identificador = identificadorDispositivo),
        )
        return when (val r = api.login(peticion)) {
            is Resultado.Fallo -> r
            is Resultado.Exito -> {
                val dto = r.valor
                almacen.guardar(dto.token)   // token -> almacenamiento seguro
                // Ktor tiene cacheado el token del usuario anterior: hay que
                // invalidarlo o la siguiente petición saldría con el Bearer viejo.
                api.olvidarCredenciales()
                val sesion = SesionActiva(
                    usuarioId = dto.usuario.id,
                    nombreCompleto = "${dto.usuario.nombres} ${dto.usuario.apellidos}",
                    rol = RolUsuario.desde(dto.usuario.rol) ?: RolUsuario.PRODUCTOR,
                    ambito = Ambito.desde(dto.ambito),
                    dispositivoId = dto.dispositivoId,
                )
                withContext(io) {
                    // La BD local es de un solo usuario. Si entra otro distinto,
                    // se descarta la caché de sincronización del anterior (filas,
                    // cursores y, sobre todo, operaciones pendientes en el outbox).
                    val previo = q.sesionActual().executeAsOneOrNull()
                    if (previo != null && previo.usuario_id != sesion.usuarioId) {
                        q.limpiarCacheLocal()
                    }
                    q.guardarSesion(sesion.usuarioId, sesion.nombreCompleto, sesion.rol.clave, sesion.ambito.parametro, sesion.dispositivoId)
                }
                Resultado.Exito(sesion)
            }
        }
    }

    override suspend fun cerrarSesion(): Resultado<Unit> {
        api.logout() // best-effort; si no hay red, igual limpiamos local
        api.olvidarCredenciales()
        almacen.borrar()
        withContext(io) {
            q.limpiarCacheLocal()
            q.borrarSesion()
        }
        return Resultado.Exito(Unit)
    }

    override suspend fun tokenActual(): String? = almacen.leer()
}
