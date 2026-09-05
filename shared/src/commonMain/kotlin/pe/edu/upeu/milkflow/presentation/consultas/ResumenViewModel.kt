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
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenProductor

class ResumenViewModel(
    private val productorRepository: ProductorRepository,
    private val obtenerResumenProductor: ObtenerResumenProductor,
    private val zonaHoraria: () -> TimeZone = { TimeZone.currentSystemDefault() },
    private val hoy: () -> LocalDate = {
        Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault()).date
    },
) : ViewModel() {
    private val _uiState = MutableStateFlow(ResumenUiState())
    val uiState: StateFlow<ResumenUiState> = _uiState.asStateFlow()

    init { cargarProductores() }

    fun onEvent(event: ResumenUiEvent) {
        when (event) {
            ResumenUiEvent.Load, ResumenUiEvent.Retry -> cargarProductores()
            is ResumenUiEvent.SelectProductor -> _uiState.update { it.copy(productorId = event.productorId) }
            is ResumenUiEvent.FechaChanged -> _uiState.update { it.copy(fechaReferencia = event.value) }
            is ResumenUiEvent.PeriodoChanged -> _uiState.update { it.copy(periodo = event.value) }
            ResumenUiEvent.Consultar -> consultar()
        }
    }

    private fun cargarProductores() {
        _uiState.update { it.copy(content = ResumenContentState.Loading) }
        viewModelScope.launch {
            try {
                val productores = productorRepository.obtenerTodos().sortedBy(Productor::nombre)
                _uiState.update {
                    it.copy(
                        productores = productores,
                        fechaReferencia = it.fechaReferencia.ifBlank { hoy().toString() },
                        content = if (productores.isEmpty()) ResumenContentState.Empty
                        else ResumenContentState.Empty,
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                _uiState.update { it.copy(content = ResumenContentState.Error("No se pudieron cargar los productores.")) }
            }
        }
    }

    private fun consultar() {
        val state = _uiState.value
        val productorId = state.productorId
        val fecha = state.fechaReferencia.parseDateOrNull()
        if (productorId == null) {
            mostrarError("Selecciona un productor.")
            return
        }
        if (fecha == null) {
            mostrarError("Usa una fecha válida con el formato AAAA-MM-DD.")
            return
        }
        _uiState.update { it.copy(content = ResumenContentState.Loading) }
        viewModelScope.launch {
            try {
                val rango = when (state.periodo) {
                    ResumenPeriodo.SEMANAL -> RangoFechas(
                        fecha.atStartOfDayIn(zonaHoraria()),
                        fecha.plus(DatePeriod(days = 7)).atStartOfDayIn(zonaHoraria()),
                    )
                    ResumenPeriodo.MENSUAL -> {
                        val inicioMes = LocalDate(fecha.year, fecha.monthNumber, 1)
                        val finMes = if (fecha.monthNumber == 12) {
                            LocalDate(fecha.year + 1, 1, 1)
                        } else {
                            LocalDate(fecha.year, fecha.monthNumber + 1, 1)
                        }
                        RangoFechas(inicioMes.atStartOfDayIn(zonaHoraria()), finMes.atStartOfDayIn(zonaHoraria()))
                    }
                }
                val resumen = obtenerResumenProductor(productorId, rango)
                _uiState.update {
                    it.copy(
                        content = if (resumen.cantidadEntregas == 0) ResumenContentState.Empty
                        else ResumenContentState.Success(resumen),
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (_: Exception) {
                mostrarError("No se pudo obtener el resumen del productor.")
            }
        }
    }

    private fun mostrarError(message: String) {
        _uiState.update { it.copy(content = ResumenContentState.Error(message)) }
    }
}
