package pe.edu.upeu.milkflow.presentation.inicio

data class InicioUiState(
    val content: InicioContentState = InicioContentState.Loading,
    val navigation: InicioNavigation? = null,
)

sealed interface InicioContentState {
    data object Loading : InicioContentState
    data class Success(val data: InicioDashboardData) : InicioContentState
    data class Empty(val data: InicioDashboardData) : InicioContentState
    data class Error(val message: String) : InicioContentState
}

data class InicioDashboardData(
    val nombreUsuario: String,
    val rol: String,
    val totalLitrosHoy: Double,
    val cantidadEntregasHoy: Int,
    val registrosPendientes: Int,
    val entregasRecientes: List<EntregaRecienteUi>,
    val acciones: List<InicioAccionUi> = emptyList(),
    val mostrarMensajePendiente: Boolean = false,
)

data class InicioAccionUi(
    val titulo: String,
    val navigation: InicioNavigation,
    val principal: Boolean = false,
)

data class EntregaRecienteUi(
    val id: String,
    val tipo: String,
    val litros: Double,
    val fechaHora: String,
)

enum class InicioNavigation {
    REGISTRAR_ENTREGA,
    PRODUCTORES,
    ACOPIADORES,
    CALIDAD,
    REPORTES,
    CONSULTAS,
    USUARIOS,
    AUDITORIA,
}

sealed interface InicioUiEvent {
    data object Refresh : InicioUiEvent
    data class Navigate(val destination: InicioNavigation) : InicioUiEvent
    data object NavigationHandled : InicioUiEvent
}
