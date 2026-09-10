package pe.edu.upeu.milkflow.ui.screens.supervisor

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.calidad.EvaluadorCalidad
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository
import pe.edu.upeu.milkflow.domain.usecase.EvaluarCalidadUseCase
import pe.edu.upeu.milkflow.domain.vo.MedicionLactoscan
import pe.edu.upeu.milkflow.ui.util.mensajeUi

data class ProductorItem(val id: String, val nombre: String, val codigoPadron: String)

data class FormLactoscan(
    val ph: String = "",
    val agua: String = "",
    val densidad: String = "",
    val temperatura: String = "",
)

data class InspeccionUiState(
    val rutaSeleccionada: String? = null,
    val productores: List<ProductorItem> = emptyList(),
    val productorSeleccionado: ProductorItem? = null,
    val form: FormLactoscan = FormLactoscan(),
    val errorForm: String? = null,
    val preview: EvaluadorCalidad.Resultado? = null,
    val registrando: Boolean = false,
    val medidaRegistrada: Boolean = false,
    val inspeccionParaImprimir: List<String>? = null,
)

class InspeccionViewModel(
    private val sesion: SesionActiva,
    private val rutas: RutaRepository,
    private val zonas: ZonaRepository,
    private val productores: ProductorRepository,
    private val sanciones: SancionRepository,
    private val evaluador: EvaluadorCalidad,
    private val evaluarCalidad: EvaluarCalidadUseCase,
) : ViewModel() {

    private val rutaSel = MutableStateFlow<String?>(null)
    private val productorSel = MutableStateFlow<ProductorItem?>(null)
    private val form = MutableStateFlow(FormLactoscan())
    private val proceso = MutableStateFlow(Proceso())

    private data class Proceso(
        val errorForm: String? = null,
        val preview: EvaluadorCalidad.Resultado? = null,
        val registrando: Boolean = false,
        val registrada: Boolean = false,
        val imprimir: List<String>? = null,
    )

    private val productoresDeRuta = combine(
        rutaSel, productores.observarPadron(), zonas.observarTodas(), rutas.observarTodas(),
    ) { codigo, padron, zs, rs ->
        if (codigo == null) return@combine emptyList()
        val rid = rs.firstOrNull { it.codigo == codigo }?.id
        val zonasRuta = zs.filter { it.rutaId == rid }.map { it.id }.toSet()
        padron.filter { it.zonaId in zonasRuta }
            .sortedBy { it.apellidos }
            .map { ProductorItem(it.id, it.nombreCompleto, it.codigoPadron) }
    }

    val estado: StateFlow<InspeccionUiState> =
        combine(rutaSel, productoresDeRuta, productorSel, form, proceso) { ruta, lista, sel, f, p ->
            InspeccionUiState(
                rutaSeleccionada = ruta,
                productores = lista,
                productorSeleccionado = sel,
                form = f,
                errorForm = p.errorForm,
                preview = p.preview,
                registrando = p.registrando,
                medidaRegistrada = p.registrada,
                inspeccionParaImprimir = p.imprimir,
            )
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), InspeccionUiState())

    fun onRuta(codigo: String) {
        rutaSel.value = codigo
        productorSel.value = null
        proceso.value = Proceso()
    }

    fun onProductor(item: ProductorItem) {
        productorSel.value = item
        form.value = FormLactoscan()
        proceso.value = Proceso()
    }

    fun onPh(v: String) = actualizarForm { it.copy(ph = limpiar(v)) }
    fun onAgua(v: String) = actualizarForm { it.copy(agua = limpiar(v)) }
    fun onDensidad(v: String) = actualizarForm { it.copy(densidad = limpiar(v)) }
    fun onTemperatura(v: String) = actualizarForm { it.copy(temperatura = limpiar(v)) }

    private fun actualizarForm(bloque: (FormLactoscan) -> FormLactoscan) {
        form.update(bloque)
        proceso.update { it.copy(errorForm = null, preview = null) }
    }

    private fun limpiar(v: String) = v.filter { it.isDigit() || it == '.' }

    /** Previsualiza el dictamen SIN persistir (usa el EvaluadorCalidad de dominio). */
    fun previsualizar() {
        val prod = productorSel.value ?: return
        val f = form.value
        val medicionR = MedicionLactoscan.de(
            aguaAnadidaPorcentaje = f.agua.toDoubleOrNull(),
            ph = f.ph.toDoubleOrNull(),
            densidad = f.densidad.toDoubleOrNull(),
            temperatura = f.temperatura.toDoubleOrNull(),
        )
        when (medicionR) {
            is Resultado.Fallo -> proceso.update { it.copy(errorForm = medicionR.error.mensajeUi(), preview = null) }
            is Resultado.Exito -> viewModelScope.launch {
                val reincide = sanciones.tieneSancionAguaVigente(prod.id)
                val veredicto = evaluador.evaluar(medicionR.valor, reincide)
                proceso.update { it.copy(errorForm = null, preview = veredicto) }
            }
        }
    }

    /** Ejecuta la medida: persiste la inspección y la encola (el backend aplica la sanción). */
    fun ejecutar() {
        val prod = productorSel.value ?: return
        val f = form.value
        val medicionR = MedicionLactoscan.de(
            aguaAnadidaPorcentaje = f.agua.toDoubleOrNull(),
            ph = f.ph.toDoubleOrNull(),
            densidad = f.densidad.toDoubleOrNull(),
            temperatura = f.temperatura.toDoubleOrNull(),
        )
        if (medicionR is Resultado.Fallo) {
            proceso.update { it.copy(errorForm = medicionR.error.mensajeUi()) }
            return
        }
        val medicion = (medicionR as Resultado.Exito).valor
        proceso.update { it.copy(registrando = true, errorForm = null) }
        viewModelScope.launch {
            val r = evaluarCalidad(
                EvaluarCalidadUseCase.Entrada(
                    productorId = prod.id,
                    supervisorId = sesion.usuarioId,
                    medicion = medicion,
                ),
            )
            when (r) {
                is Resultado.Fallo -> proceso.update { it.copy(registrando = false, errorForm = r.error.mensajeUi()) }
                is Resultado.Exito -> {
                    val i = r.valor
                    val ticket = listOf(
                        "Inspección ${i.id.take(8).uppercase()}",
                        "Productor: ${prod.nombre} (${prod.codigoPadron})",
                        "Agua: ${f.agua.ifBlank { "—" }}%   pH: ${f.ph.ifBlank { "—" }}",
                        "Densidad: ${f.densidad.ifBlank { "—" }}   Temp: ${f.temperatura.ifBlank { "—" }}",
                        "--------------------------------",
                        "Dictamen: ${i.dictamen.clave}",
                        i.dictamenDetalle,
                        if (i.esReincidencia) "REINCIDENCIA" else "",
                    ).filter { it.isNotBlank() }
                    proceso.update { it.copy(registrando = false, registrada = true, imprimir = ticket) }
                }
            }
        }
    }

    fun reiniciar() {
        productorSel.value = null
        form.value = FormLactoscan()
        proceso.value = Proceso()
    }
}
