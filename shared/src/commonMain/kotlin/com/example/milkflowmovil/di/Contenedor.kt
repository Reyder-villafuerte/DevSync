package com.example.milkflowmovil.di

import com.example.milkflowmovil.data.local.BaseLocal
import com.example.milkflowmovil.data.remote.MilkFlowRemoteDataSource
import com.example.milkflowmovil.data.remote.crearMotorHttp
import com.example.milkflowmovil.data.remote.createHttpClient
import com.example.milkflowmovil.data.repository.AcopioRepositoryImpl
import com.example.milkflowmovil.data.repository.AdministracionRepositoryImpl
import com.example.milkflowmovil.data.repository.CalidadRepositoryImpl
import com.example.milkflowmovil.data.repository.ColaOperaciones
import com.example.milkflowmovil.data.repository.PagosRepositoryImpl
import com.example.milkflowmovil.data.repository.PlantaRepositoryImpl
import com.example.milkflowmovil.data.repository.SesionRepositoryImpl
import com.example.milkflowmovil.data.repository.SincronizacionRepositoryImpl
import com.example.milkflowmovil.data.repository.VentasRepositoryImpl
import com.example.milkflowmovil.data.repository.ZonasRepositoryImpl
import com.example.milkflowmovil.data.sync.Sincronizador
import com.example.milkflowmovil.domain.repository.AcopioRepository
import com.example.milkflowmovil.domain.repository.AdministracionRepository
import com.example.milkflowmovil.domain.repository.CalidadRepository
import com.example.milkflowmovil.domain.repository.PagosRepository
import com.example.milkflowmovil.domain.repository.PlantaRepository
import com.example.milkflowmovil.domain.repository.SesionRepository
import com.example.milkflowmovil.domain.repository.SincronizacionRepository
import com.example.milkflowmovil.domain.repository.VentasRepository
import com.example.milkflowmovil.domain.repository.ZonasRepository
import com.example.milkflowmovil.domain.usecase.AbrirRutaDeHoyUseCase
import com.example.milkflowmovil.domain.usecase.ActualizarTarifasUseCase
import com.example.milkflowmovil.domain.usecase.AutorizarPagoUseCase
import com.example.milkflowmovil.domain.usecase.AutorizarTodosLosPagosUseCase
import com.example.milkflowmovil.domain.usecase.CambiarServidorUseCase
import com.example.milkflowmovil.domain.usecase.CerrarCajaUseCase
import com.example.milkflowmovil.domain.usecase.CerrarRutaUseCase
import com.example.milkflowmovil.domain.usecase.CerrarSesionUseCase
import com.example.milkflowmovil.domain.usecase.CompletarVisitaUseCase
import com.example.milkflowmovil.domain.usecase.DescartarRechazadaUseCase
import com.example.milkflowmovil.domain.usecase.EntregarSobreUseCase
import com.example.milkflowmovil.domain.usecase.IniciarAppUseCase
import com.example.milkflowmovil.domain.usecase.IniciarSesionUseCase
import com.example.milkflowmovil.domain.usecase.ObservarEstadoUseCase
import com.example.milkflowmovil.domain.usecase.ObservarSincronizacionUseCase
import com.example.milkflowmovil.domain.usecase.PublicarAvisoUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarAnalisisUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarEgresoUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarEntregaUseCase
import com.example.milkflowmovil.domain.usecase.RegistrarVentaUseCase
import com.example.milkflowmovil.domain.usecase.RevisarSolicitudZonaUseCase
import com.example.milkflowmovil.domain.usecase.SincronizarAhoraUseCase
import com.example.milkflowmovil.domain.usecase.SolicitarCambioZonaUseCase
import com.example.milkflowmovil.domain.usecase.VerificarRecepcionUseCase
import com.example.milkflowmovil.presentation.viewmodel.AcopioViewModel
import com.example.milkflowmovil.presentation.viewmodel.AdministracionViewModel
import com.example.milkflowmovil.presentation.viewmodel.CalidadViewModel
import com.example.milkflowmovil.presentation.viewmodel.PagosViewModel
import com.example.milkflowmovil.presentation.viewmodel.PanelViewModel
import com.example.milkflowmovil.presentation.viewmodel.PlantaViewModel
import com.example.milkflowmovil.presentation.viewmodel.ProductorViewModel
import com.example.milkflowmovil.presentation.viewmodel.SesionViewModel
import com.example.milkflowmovil.presentation.viewmodel.SincronizacionViewModel
import com.example.milkflowmovil.presentation.viewmodel.VentasViewModel
import com.example.milkflowmovil.presentation.viewmodel.ZonasViewModel
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob

/**
 * Armado manual de dependencias, capa por capa:
 *
 *   DATOS      HttpClient -> RemoteDataSource + BaseLocal -> Sincronizador -> RepositoryImpl
 *   DOMINIO    Repository (interfaz) -> UseCase
 *   PRESENTACIÓN  UseCase -> ViewModel
 *
 * Es un único objeto para toda la app: si Android recrea la pantalla (por
 * ejemplo al girar el teléfono) la base local y la cola no se duplican.
 */
object Contenedor {
    private val alcance = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    // ------------------------------------------------------------- DATOS

    private val base = BaseLocal(alcance = alcance)

    private val httpClient = createHttpClient(crearMotorHttp()) { base.actual.urlBase }

    private val remoto = MilkFlowRemoteDataSource(httpClient)

    private val sincronizador = Sincronizador(base, remoto)

    private val cola = ColaOperaciones(base, sincronizador, alcance)

    private val sesionRepository: SesionRepository = SesionRepositoryImpl(base, remoto, sincronizador, cola, alcance)
    private val sincronizacionRepository: SincronizacionRepository = SincronizacionRepositoryImpl(sincronizador, cola)
    private val acopioRepository: AcopioRepository = AcopioRepositoryImpl(cola)
    private val plantaRepository: PlantaRepository = PlantaRepositoryImpl(cola)
    private val ventasRepository: VentasRepository = VentasRepositoryImpl(cola)
    private val calidadRepository: CalidadRepository = CalidadRepositoryImpl(cola)
    private val zonasRepository: ZonasRepository = ZonasRepositoryImpl(cola)
    private val pagosRepository: PagosRepository = PagosRepositoryImpl(cola)
    private val administracionRepository: AdministracionRepository = AdministracionRepositoryImpl(cola)

    // ----------------------------------------------------------- DOMINIO

    private val observarEstado = ObservarEstadoUseCase(sesionRepository)
    private val cambiarServidor = CambiarServidorUseCase(sesionRepository)

    // ------------------------------------------------------ PRESENTACIÓN

    fun sesionViewModel() = SesionViewModel(
        observarEstado,
        IniciarAppUseCase(sesionRepository),
        IniciarSesionUseCase(sesionRepository),
        CerrarSesionUseCase(sesionRepository),
        cambiarServidor,
    )

    fun sincronizacionViewModel() = SincronizacionViewModel(
        observarEstado,
        ObservarSincronizacionUseCase(sincronizacionRepository),
        SincronizarAhoraUseCase(sincronizacionRepository),
        cambiarServidor,
        DescartarRechazadaUseCase(sincronizacionRepository),
    )

    fun panelViewModel() = PanelViewModel(observarEstado)

    fun acopioViewModel() = AcopioViewModel(
        observarEstado,
        AbrirRutaDeHoyUseCase(acopioRepository),
        RegistrarEntregaUseCase(acopioRepository),
        CerrarRutaUseCase(acopioRepository),
    )

    fun plantaViewModel() = PlantaViewModel(
        observarEstado,
        VerificarRecepcionUseCase(plantaRepository),
    )

    fun ventasViewModel() = VentasViewModel(
        observarEstado,
        RegistrarVentaUseCase(ventasRepository),
        CerrarCajaUseCase(ventasRepository),
    )

    fun calidadViewModel() = CalidadViewModel(
        observarEstado,
        RegistrarAnalisisUseCase(calidadRepository),
        CompletarVisitaUseCase(calidadRepository),
    )

    fun zonasViewModel() = ZonasViewModel(
        observarEstado,
        RevisarSolicitudZonaUseCase(zonasRepository),
    )

    fun productorViewModel() = ProductorViewModel(
        observarEstado,
        SolicitarCambioZonaUseCase(zonasRepository),
    )

    fun pagosViewModel() = PagosViewModel(
        observarEstado,
        AutorizarPagoUseCase(pagosRepository),
        AutorizarTodosLosPagosUseCase(pagosRepository),
        EntregarSobreUseCase(pagosRepository),
    )

    fun administracionViewModel() = AdministracionViewModel(
        observarEstado,
        RegistrarEgresoUseCase(administracionRepository),
        ActualizarTarifasUseCase(administracionRepository),
        PublicarAvisoUseCase(administracionRepository),
    )
}
