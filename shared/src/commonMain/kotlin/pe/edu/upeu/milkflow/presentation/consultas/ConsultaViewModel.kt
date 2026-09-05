package pe.edu.upeu.milkflow.presentation.consultas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.time.Clock
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.DatePeriod
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import kotlinx.datetime.plus
import kotlinx.datetime.toLocalDateTime
import pe.edu.upeu.milkflow.domain.RangoFechasInvalidoException
import pe.edu.upeu.milkflow.domain.model.Entrega
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasProductor

class ConsultaViewModel(
    private val productorRepository: ProductorRepository,
    private val obtenerEntregasProductor: ObtenerEntregasProductor,
    private val zonaHoraria: () -> TimeZone = { TimeZone.currentSystemDefault() },
    private val hoy: () -> LocalDate = {
        Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault()).date
    },
) : ViewModel() {
    private val _uiState = MutableStateFlow(ConsultaUiState())
    val uiState: StateFlow<ConsultaUiState> = _uiState.asStateFlow()

    init { cargarProductores() }

    fun onEvent(event: ConsultaUiEvent) {
        when (event) {
            ConsultaUiEvent.Load, ConsultaUiEvent.Retry -> cargarProductores()
            is ConsultaUiEvent.SelectProductor -> _uiState.update { it.copy(productorId = event.productorId) }
            is ConsultaUiEvent.FechaInicioChanged -> _uiState.update { it.copy(fechaInicio = event.value) }
            is ConsultaUiEvent.FechaFinChanged -> _uiState.update { it.copy(fechaFin = event.value) }
            ConsultaUiEvent.Buscar -> buscar()
        }
    }

    private fun cargarProductores() {
        _uiState.update { it.copy(content = ConsultaContentState.Loading) }
        viewModelScope.launch {
            try {
                val productores = productorRepository.obtenerTodos().sortedBy(Productor::nombre)
                val fecha = hoy().toString()
                _uiState.update {
                    it.copy(
                        productores = productores,
                        fechaInicio = it.fechaInicio.ifBlank { fecha },
                        fechaFin = it.fechaFin.ifBlank { fecha },
                        content = if (productores.isEmpty()) ConsultaContentState.Empty
                        else ConsultaContentState.Empty,
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update { it.copy(content = ConsultaContentState.Error("No se pudieron cargar los productores.")) }
            }
        }
    }

    private fun buscar() {
        val state = _uiState.value
        val productorId = state.productorId
        val inicio = state.fechaInicio.parseDateOrNull()
        val fin = state.fechaFin.parseDateOrNull()
        if (productorId == null) {
            mostrarError("Selecciona un productor.")
            return
        }
        if (inicio == null || fin == null) {
            mostrarError("Usa fechas válidas con el formato AAAA-MM-DD.")
            return
        }
        if (inicio > fin) {
            mostrarError("La fecha inicial no puede ser posterior a la fecha final.")
            return
        }
        _uiState.update { it.copy(content = ConsultaContentState.Loading) }
        viewModelScope.launch {
            try {
                val rango = RangoFechas(
                    inicio = inicio.atStartOfDayIn(zonaHoraria()),
                    finExclusivo = fin.plus(DatePeriod(days = 1)).atStartOfDayIn(zonaHoraria()),
                )
                val entregas = obtenerEntregasProductor(productorId, rango)
                    .sortedByDescending(Entrega::fechaHora)
                    .map { it.toConsultaUi() }
                _uiState.update {
                    it.copy(
                        content = if (entregas.isEmpty()) ConsultaContentState.Empty
                        else ConsultaContentState.Success(entregas),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: RangoFechasInvalidoException) {
                mostrarError("El rango de fechas no es válido.")
            } catch (_: Exception) {
                mostrarError("No se pudieron consultar las entregas del productor.")
            }
        }
    }

    private fun mostrarError(message: String) {
        _uiState.update { it.copy(content = ConsultaContentState.Error(message)) }
    }
}

internal fun String.parseDateOrNull(): LocalDate? = runCatching { LocalDate.parse(trim()) }.getOrNull()

private fun Entrega.toConsultaUi() = ConsultaEntregaUi(
    id = id,
    fechaHora = fechaHora.toString(),
    litros = litros,
    tipo = tipo,
    estadoSincronizacion = estadoSincronizacion,
    acopiadorId = acopiadorId,
    sector = sector,
)
