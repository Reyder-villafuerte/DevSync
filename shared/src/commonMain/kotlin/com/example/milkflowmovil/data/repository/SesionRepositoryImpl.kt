package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.core.Resultado
import com.example.milkflowmovil.data.local.BaseLocal
import com.example.milkflowmovil.data.local.identificadorDispositivo
import com.example.milkflowmovil.data.mapper.toDomain
import com.example.milkflowmovil.data.remote.MilkFlowRemoteDataSource
import com.example.milkflowmovil.data.sync.Sincronizador
import com.example.milkflowmovil.domain.model.EstadoApp
import com.example.milkflowmovil.domain.model.Sesion
import com.example.milkflowmovil.domain.repository.SesionRepository
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

/**
 * Sesión y estado general: combina la base local (el teléfono) con la fuente
 * remota (la API). Aquí la respuesta del login se traduce de DTO a dominio.
 */
class SesionRepositoryImpl(
    private val base: BaseLocal,
    private val api: MilkFlowRemoteDataSource,
    private val sincronizador: Sincronizador,
    private val cola: ColaOperaciones,
    private val alcance: CoroutineScope,
) : SesionRepository {

    override val estado: StateFlow<EstadoApp> = base.estado

    override val actual: EstadoApp get() = base.actual

    private val dispositivoId = identificadorDispositivo()

    /** Evita arrancar dos veces el ciclo automático si la pantalla se recrea (por ejemplo al girar). */
    private var iniciado = false

    override suspend fun iniciar() {
        if (iniciado) return
        iniciado = true

        base.cargar()
        if (base.actual.sesion != null) {
            sincronizador.sincronizar()
        }
        arrancarCicloAutomatico()
    }

    override suspend fun iniciarSesion(usuario: String, password: String): Resultado<Unit> {
        return when (val respuesta = api.login(usuario.trim(), password, dispositivoId)) {
            is Resultado.Fallo -> Resultado.Fallo(respuesta.error)
            is Resultado.Exito -> {
                val datos = respuesta.valor
                val usuarioSesion = datos.usuario.toDomain()
                val urlActual = base.actual.urlBase
                val eraOtroUsuario = base.actual.sesion?.usuario?.id != usuarioSesion.id

                // La base local es de una sola persona: si entra otra, se limpia.
                if (eraOtroUsuario) base.limpiar(urlActual)

                base.actualizar { estado ->
                    estado.copy(
                        sesion = Sesion(
                            token = datos.token,
                            usuario = usuarioSesion,
                            dispositivoId = dispositivoId,
                            iniciadaEn = Fechas.ahoraIso(),
                        ),
                        avisos = datos.avisos.map { it.toDomain() }.ifEmpty { estado.avisos },
                    )
                }

                // La primera bajada se lanza en el ámbito del repositorio: la
                // pantalla de login desaparece en cuanto hay sesión y con ella
                // se cancelaría la petición a media descarga.
                cola.sincronizarEnSegundoPlano()
                Resultado.Exito(Unit)
            }
        }
    }

    override suspend fun cerrarSesion() {
        val token = base.actual.sesion?.token
        val url = base.actual.urlBase
        if (token != null) api.cerrarSesion(token)
        base.limpiar(url)
    }

    override fun cambiarServidor(url: String) {
        base.actualizar { it.copy(urlBase = url.trim().trimEnd('/')) }
    }

    /**
     * Sincronización casi en tiempo real: cada 10 segundos sube lo pendiente y
     * baja lo que cambió en la web (rutas asignadas, verificaciones de planta,
     * avisos...). La bajada es incremental por cursor, así que solo viajan las
     * filas nuevas. Sin señal simplemente falla en silencio y vuelve a intentar.
     */
    private fun arrancarCicloAutomatico() {
        alcance.launch {
            while (isActive) {
                delay(INTERVALO_SINCRONIZACION_MS)

                if (base.actual.sesion != null) {
                    sincronizador.sincronizar()
                }
            }
        }
    }

    private companion object {
        const val INTERVALO_SINCRONIZACION_MS = 10_000L
    }
}
