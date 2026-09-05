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
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.TipoEntrega
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasRecientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class InicioViewModel(
    private val sesionUsuario: SesionUsuario,
    private val obtenerReporteDiario: ObtenerReporteDiario,
    private val obtenerEntregasRecientes: ObtenerEntregasRecientes,
    private val obtenerRegistrosPendientes: ObtenerRegistrosPendientes,
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
                val data = InicioDashboardData(
                    nombreUsuario = usuario.nombre,
                    rol = usuario.rol.nombreVisible(),
                    totalLitrosHoy = reporte.totalLitros,
                    cantidadEntregasHoy = reporte.cantidadEntregas,
                    registrosPendientes = pendientes,
                    entregasRecientes = recientes.map(Entrega::toRecentUi),
                    acciones = calcularAcciones(usuario.rol),
                    mostrarMensajePendiente = usuario.rol == RolUsuario.DESPACHO_QUESO
                )
                _uiState.update {
                    it.copy(
                        content = if (reporte.cantidadEntregas == 0 && usuario.rol != RolUsuario.DESPACHO_QUESO) {
                            InicioContentState.Empty(data)
                        } else {
                            InicioContentState.Success(data)
                        },
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(
                        content = InicioContentState.Error(
                            "No se pudo cargar la información del inicio.",
                        ),
                    )
                }
            }
        }
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
            InicioAccionUi("Registrar entrega", InicioNavigation.REGISTRAR_ENTREGA, true),
            InicioAccionUi("Productores", InicioNavigation.PRODUCTORES),
            InicioAccionUi("Entregas hoy", InicioNavigation.CONSULTAS),
            InicioAccionUi("Reportes", InicioNavigation.REPORTES),
        )
        RolUsuario.ACOPIADOR -> listOf(
            InicioAccionUi("Nueva recolección", InicioNavigation.REGISTRAR_ENTREGA, true),
            InicioAccionUi("Productores", InicioNavigation.PRODUCTORES),
            InicioAccionUi("Mis entregas", InicioNavigation.CONSULTAS),
        )
        RolUsuario.SUPERVISOR -> listOf(
            InicioAccionUi("Control de calidad", InicioNavigation.CALIDAD, true),
            InicioAccionUi("Entregas por revisar", InicioNavigation.CONSULTAS),
            InicioAccionUi("Problemas registrados", InicioNavigation.REPORTES),
        )
        RolUsuario.DESPACHO_QUESO, RolUsuario.PENDIENTE_ASIGNACION -> emptyList()
    }
}

private fun RolUsuario.nombreVisible(): String = when (this) {
    RolUsuario.ADMINISTRADORA -> "Administradora"
    RolUsuario.JEFE_PRODUCCION -> "Jefe de producción"
    RolUsuario.DESPACHO_QUESO -> "Despacho de queso"
    RolUsuario.ACOPIADOR -> "Acopiador"
    RolUsuario.SUPERVISOR -> "Supervisor"
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
