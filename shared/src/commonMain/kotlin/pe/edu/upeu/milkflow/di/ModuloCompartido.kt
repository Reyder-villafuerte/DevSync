package pe.edu.upeu.milkflow.di

import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlin.time.Clock
import org.koin.core.module.Module
import org.koin.core.qualifier.named
import org.koin.dsl.module
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.GeneradorUuid
import pe.edu.upeu.milkflow.core.NivelLogHttp
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.data.local.crearBaseDatos
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.local.repository.AvisoRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.InspeccionRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.JornadaRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.LiquidacionRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.PrecioRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.ProductorRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.RecoleccionRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.RutaRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.SancionRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.SincronizacionRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.SolicitudRutaRepositoryLocal
import pe.edu.upeu.milkflow.data.local.repository.ZonaRepositoryLocal
import pe.edu.upeu.milkflow.data.remote.ApiMilkFlow
import pe.edu.upeu.milkflow.data.remote.AplicadorCambios
import pe.edu.upeu.milkflow.data.remote.ClienteSincronizacionHttp
import pe.edu.upeu.milkflow.data.remote.SesionRepositoryHttp
import pe.edu.upeu.milkflow.data.remote.construirClienteMilkFlow
import pe.edu.upeu.milkflow.domain.calidad.EvaluadorCalidad
import pe.edu.upeu.milkflow.domain.repository.AvisoRepository
import pe.edu.upeu.milkflow.domain.repository.InspeccionRepository
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.LiquidacionRepository
import pe.edu.upeu.milkflow.domain.repository.PrecioRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.repository.SesionRepository
import pe.edu.upeu.milkflow.domain.repository.SolicitudRutaRepository
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository
import pe.edu.upeu.milkflow.domain.sync.ClienteSincronizacion
import pe.edu.upeu.milkflow.domain.sync.PoliticaReintento
import pe.edu.upeu.milkflow.domain.sync.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.sync.SyncManager
import pe.edu.upeu.milkflow.domain.usecase.CerrarJornadaUseCase
import pe.edu.upeu.milkflow.domain.usecase.EvaluarCalidadUseCase
import pe.edu.upeu.milkflow.domain.usecase.IniciarJornadaUseCase
import pe.edu.upeu.milkflow.domain.usecase.IniciarSesionUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerAvisoActivoUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEstadoSincronizacionUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteAcopioUseCase
import pe.edu.upeu.milkflow.domain.usecase.RegistrarRecoleccionUseCase
import pe.edu.upeu.milkflow.domain.usecase.SincronizarAhoraUseCase
import pe.edu.upeu.milkflow.domain.usecase.SolicitarCambioRutaUseCase
import pe.edu.upeu.milkflow.infra.almacen.AlmacenSeguroToken


/** Configuración de arranque que la app (Android/iOS) pasa a Koin. */
data class ConfiguracionMilkFlow(
    val urlBase: String,
    val nivelLogHttp: NivelLogHttp = NivelLogHttp.BASICO,
)

internal val IO = named("io")
internal val ALCANCE_SYNC = named("alcanceSync")

/**
 * Módulo común: todo lo que no depende de plataforma. Los `DriverFactory`,
 * `AlmacenSeguroToken` y `ObservadorConectividad` los aporta cada módulo de
 * plataforma ([moduloPlataforma]).
 */
fun moduloCompartido(config: ConfiguracionMilkFlow): Module = module {

    single { config }
    single<Reloj> { Reloj { Clock.System.now() } }
    single<GeneradorId> { GeneradorUuid() }
    single(IO) { Dispatchers.Default }
    single(ALCANCE_SYNC) { CoroutineScope(SupervisorJob() + Dispatchers.Default) }
    single { PoliticaReintento() }
    single { EvaluadorCalidad() }

    // --- Persistencia local ---
    single<MilkFlowDatabase> { crearBaseDatos(get()) }

    // --- HTTP ---
    single {
        construirClienteMilkFlow(
            urlBase = get<ConfiguracionMilkFlow>().urlBase,
            proveedorToken = { get<AlmacenSeguroToken>().leer() },   // rompe el ciclo con SesionRepository
            registrar = { /* enrutar a Napier/logcat desde la app si se desea */ },
            nivelLog = get<ConfiguracionMilkFlow>().nivelLogHttp,
        )
    }
    single { ApiMilkFlow(get()) }
    single { AplicadorCambios(get(), get(IO)) }

    // --- Repositorios ---
    single<SesionRepository> { SesionRepositoryHttp(get(), get(), get(), get(IO)) }
    single<SincronizacionRepository> { SincronizacionRepositoryLocal(get(), get(IO)) }
    single<ClienteSincronizacion> { ClienteSincronizacionHttp(get(), get()) }

    single<ProductorRepository> { ProductorRepositoryLocal(get(), get(IO)) }
    single<ZonaRepository> { ZonaRepositoryLocal(get(), get(IO)) }
    single<RutaRepository> { RutaRepositoryLocal(get(), get(IO)) }
    single<SancionRepository> { SancionRepositoryLocal(get(), get(IO)) }
    single<PrecioRepository> { PrecioRepositoryLocal(get(), get(IO)) }
    single<LiquidacionRepository> { LiquidacionRepositoryLocal(get(), get(IO)) }
    single<RecoleccionRepository> { RecoleccionRepositoryLocal(get(), get(IO)) }
    single<InspeccionRepository> { InspeccionRepositoryLocal(get(), get(IO)) }
    single<SolicitudRutaRepository> { SolicitudRutaRepositoryLocal(get(), get(IO)) }
    single<JornadaRepository> { JornadaRepositoryLocal(get(), get(IO), get(), get()) }
    single<AvisoRepository> {
        AvisoRepositoryLocal(get(), get(IO), get(), get()) {
            get<SesionRepository>().sesionActual()?.usuarioId
        }
    }

    // --- Motor de sincronización ---
    single {
        SyncManager(
            sincronizacion = get(),
            cliente = get(),
            sesion = get(),
            conectividad = get(),
            politica = get(),
            reloj = get(),
            alcance = get(ALCANCE_SYNC),
        )
    }

    // --- Use cases ---
    factory { RegistrarRecoleccionUseCase(get(), get(), get(), get(), get()) }
    factory { CerrarJornadaUseCase(get(), get(), get()) }
    factory { EvaluarCalidadUseCase(get(), get(), get(), get(), get(), get()) }
    factory { ObtenerAvisoActivoUseCase(get(), get()) }
    factory { SolicitarCambioRutaUseCase(get(), get(), get(), get()) }
    factory { ObtenerReporteAcopioUseCase(get()) }
    factory { IniciarJornadaUseCase(get()) }
    factory {
        // La primera bajada la dispara el propio login (ver IniciarSesionUseCase).
        val syncManager = get<SyncManager>()
        IniciarSesionUseCase(get()) { syncManager.sincronizarEnSegundoPlano() }
    }
    factory { SincronizarAhoraUseCase(get()) }
    factory { ObtenerEstadoSincronizacionUseCase(get()) }
}

/** Módulo por plataforma (drivers, almacén seguro, conectividad). */
expect fun moduloPlataforma(): Module
