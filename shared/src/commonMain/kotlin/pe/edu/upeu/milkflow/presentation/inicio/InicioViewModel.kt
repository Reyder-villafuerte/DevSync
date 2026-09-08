package pe.edu.upeu.milkflow.presentation.inicio

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlin.time.Instant
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasRecientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerLotesProduccion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenCalidad
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenProduccion
import pe.edu.upeu.milkflow.domain.usecase.ResumenCalidad
import pe.edu.upeu.milkflow.domain.usecase.ResumenProduccion
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class InicioViewModel(
    private val sesionUsuario: SesionUsuario,
    private val obtenerReporteDiario: ObtenerReporteDiario,
    private val obtenerEntregasRecientes: ObtenerEntregasRecientes,
    private val obtenerRegistrosPendientes: ObtenerRegistrosPendientes,
    private val obtenerResumenProduccion: ObtenerResumenProduccion,
    private val obtenerLotesProduccion: ObtenerLotesProduccion,
    private val obtenerResumenCalidad: ObtenerResumenCalidad,
    private val ahora: () -> Instant = { Clock.System.now() },
    private val zonaHoraria: () -> TimeZone = { TimeZone.currentSystemDefault() },
) : ViewModel() {
    private val _uiState = MutableStateFlow(InicioUiState())
    val uiState: StateFlow<InicioUiState> = _uiState.asStateFlow()

    init {
        cargarDashboard()
    }

    fun onEvent(event: InicioUiEvent) {
        when (event) {
            InicioUiEvent.Refresh -> cargarDashboard()
            is InicioUiEvent.Navigate -> _uiState.update {
                it.copy(navigation = event.destination)
            }
            InicioUiEvent.NavigationHandled -> _uiState.update {
                it.copy(navigation = null)
            }
        }
    }

    private fun cargarDashboard() {
        _uiState.update { it.copy(content = InicioContentState.Loading) }
        viewModelScope.launch {
            val usuario = sesionUsuario.usuario.value
            if (usuario == null) {
                _uiState.update {
                    it.copy(content = InicioContentState.Error("No hay una sesión activa."))
                }
                return@launch
            }

            try {
                val zone = zonaHoraria()
                val fecha = ahora().toLocalDateTime(zone).date
                val reporte = obtenerReporteDiario(fecha, zone)
                val recientes = obtenerEntregasRecientes(reporte.rango)
                val pendientes = obtenerRegistrosPendientes().first().size
                
                val resumenProd = if (usuario.rol == RolUsuario.JEFE_PRODUCCION || usuario.rol == RolUsuario.ADMINISTRADORA) {
                    obtenerResumenProduccion(reporte.rango)
                } else null

                val resumenCal = if (usuario.rol == RolUsuario.SUPERVISOR) {
                    obtenerResumenCalidad(reporte.rango)
                } else null

                val data = InicioDashboardData(
                    nombreUsuario = usuario.nombre,
                    rol = usuario.rol.nombreVisible(),
                    kpis = calcularKpis(usuario.rol, reporte.totalLitros, reporte.cantidadEntregas, resumenProd, resumenCal),
                    registrosPendientes = pendientes,
                    entregasRecientes = when (usuario.rol) {
                        RolUsuario.JEFE_PRODUCCION -> obtenerLotesProduccion(reporte.rango).map { it.toRecentUi() }
                        RolUsuario.DESPACHO_QUESO -> emptyList()
                        else -> recientes.map(Entrega::toRecentUi)
                    },
                    acciones = calcularAcciones(usuario.rol),
                    mostrarMensajeDespacho = usuario.rol == RolUsuario.DESPACHO_QUESO,
                    esProductor = usuario.rol == RolUsuario.PRODUCTOR,
                    emptyStateTitle = when (usuario.rol) {
                        RolUsuario.SUPERVISOR -> "Sin inspecciones hoy"
                        else -> "Sin registros hoy"
                    },
                    emptyStateMessage = when (usuario.rol) {
                        RolUsuario.SUPERVISOR -> "Las pruebas de calidad evaluadas aparecerán aquí."
                        else -> "La actividad diaria aparecerá en esta sección."
                    }
                )
                
                _uiState.update {
                    it.copy(
                        content = if (reporte.cantidadEntregas == 0 && 
                                      usuario.rol != RolUsuario.DESPACHO_QUESO && 
                                      usuario.rol != RolUsuario.JEFE_PRODUCCION) {
                            InicioContentState.Empty(data)
                        } else {
                            InicioContentState.Success(data)
                        },
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        content = InicioContentState.Error(
                            "No se pudo cargar la información del inicio: ${e.message}",
                        ),
                    )
                }
            }
        }
    }

    private fun calcularKpis(
        rol: RolUsuario, 
        totalLitros: Double, 
        cantEntregas: Int,
        resumenProduccion: ResumenProduccion?,
        resumenCalidad: ResumenCalidad?
    ): List<InicioKpiUi> = when (rol) {
        RolUsuario.ADMINISTRADORA, RolUsuario.ACOPIADOR, RolUsuario.PRODUCTOR -> listOf(
            InicioKpiUi(if (rol == RolUsuario.ACOPIADOR) "Litros recogidos hoy" else "Total de litros", "$totalLitros L"),
            InicioKpiUi(if (rol == RolUsuario.ACOPIADOR) "Recojos realizados" else "Entregas hoy", "$cantEntregas")
        )
        RolUsuario.JEFE_PRODUCCION -> listOf(
            InicioKpiUi("Leche procesada hoy", "${resumenProduccion?.lecheProcesada ?: 0.0} L"),
            InicioKpiUi("Quesos producidos", "${resumenProduccion?.quesosProducidos ?: 0}"),
            InicioKpiUi("Rendimiento", resumenProduccion?.rendimientoPromedio?.let { 
                "${(it * 10).toInt() / 10.0} m/100L" 
            } ?: "0%")
        )
        RolUsuario.SUPERVISOR -> listOf(
            InicioKpiUi("Muestras evaluadas", "${resumenCalidad?.muestrasHoy ?: 0}"),
            InicioKpiUi("Adulteraciones", "${resumenCalidad?.adulteracionesHoy ?: 0}"),
            InicioKpiUi("Rechazos por acidez", "${resumenCalidad?.rechazosHoy ?: 0}")
        )
        RolUsuario.DESPACHO_QUESO -> listOf(
            InicioKpiUi("Stock disponible", "0"), // Placeholder
            InicioKpiUi("Quesos vendidos hoy", "0") // Placeholder
        )
        RolUsuario.PENDIENTE_ASIGNACION -> emptyList()
    }

    private fun calcularAcciones(rol: RolUsuario): List<InicioAccionUi> = when (rol) {
        RolUsuario.ADMINISTRADORA -> listOf(
            InicioAccionUi("Usuarios y permisos", InicioNavigation.USUARIOS, true),
            InicioAccionUi("Acopiadores", InicioNavigation.ACOPIADORES),
            InicioAccionUi("Productores", InicioNavigation.PRODUCTORES),
            InicioAccionUi("Entregas", InicioNavigation.REGISTRAR_ENTREGA),
            InicioAccionUi("Reportes", InicioNavigation.REPORTES),
            InicioAccionUi("Auditoría", InicioNavigation.AUDITORIA),
        )
        RolUsuario.JEFE_PRODUCCION -> listOf(
            InicioAccionUi("Registrar Lote de Producción", InicioNavigation.REGISTRAR_LOTE, true),
            InicioAccionUi("Lotes de producción", InicioNavigation.PRODUCCION),
            InicioAccionUi("Producción de hoy", InicioNavigation.PRODUCCION_HOY),
            InicioAccionUi("Reportes", InicioNavigation.REPORTES),
            InicioAccionUi("Sincronización", InicioNavigation.SINCRONIZACION),
        )
        RolUsuario.ACOPIADOR -> listOf(
            InicioAccionUi("Nueva recolección", InicioNavigation.REGISTRAR_ENTREGA, true),
            InicioAccionUi("Productores", InicioNavigation.PRODUCTORES),
            InicioAccionUi("Entregas hoy", InicioNavigation.CONSULTAS),
            InicioAccionUi("Sincronización", InicioNavigation.SINCRONIZACION),
        )
        RolUsuario.SUPERVISOR -> listOf(
            InicioAccionUi("Control de calidad", InicioNavigation.CALIDAD, true),
            InicioAccionUi("Entregas por revisar", InicioNavigation.CALIDAD_PENDIENTE),
            InicioAccionUi("Problemas registrados", InicioNavigation.CALIDAD_PROBLEMAS),
            InicioAccionUi("Inspecciones de hoy", InicioNavigation.CALIDAD_HOY),
        )
        RolUsuario.DESPACHO_QUESO -> listOf(
            InicioAccionUi("Registrar Venta de Queso", InicioNavigation.REGISTRAR_VENTA, true),
            InicioAccionUi("Ventas", InicioNavigation.VENTAS),
            InicioAccionUi("Stock", InicioNavigation.VENTAS),
            InicioAccionUi("Historial", InicioNavigation.AUDITORIA),
        )
        RolUsuario.PRODUCTOR -> listOf(
            InicioAccionUi("Mis entregas", InicioNavigation.MIS_ENTREGAS),
            InicioAccionUi("Resultados calidad", InicioNavigation.CALIDAD),
            InicioAccionUi("Comunicados planta", InicioNavigation.CONSULTAS),
        )
        RolUsuario.PENDIENTE_ASIGNACION -> emptyList()
    }
}

private fun RolUsuario.nombreVisible(): String = when (this) {
    RolUsuario.ADMINISTRADORA -> "Administradora"
    RolUsuario.JEFE_PRODUCCION -> "Jefe de producción"
    RolUsuario.DESPACHO_QUESO -> "Personal de despacho de queso"
    RolUsuario.ACOPIADOR -> "Acopiador"
    RolUsuario.SUPERVISOR -> "Supervisor"
    RolUsuario.PRODUCTOR -> "Productor"
    RolUsuario.PENDIENTE_ASIGNACION -> "Pendiente de asignación"
}

private fun Entrega.toRecentUi(): EntregaRecienteUi = EntregaRecienteUi(
    id = id,
    tipo = when (tipo) {
        TipoEntrega.DIRECTA -> "Directa"
        TipoEntrega.RECOGIDA -> "Recogida"
    },
    litros = litros,
    fechaHora = fechaHora.toString(),
)

private fun LoteProduccion.toRecentUi(): EntregaRecienteUi = EntregaRecienteUi(
    id = id,
    tipo = tipoProducto.nombreVisible(),
    litros = litrosLecheUtilizados,
    fechaHora = fechaHora.toString(),
)
