package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.domain.model.Aviso
import pe.edu.upeu.milkflow.domain.repository.AvisoRepository

/**
 * Intención: "la app del productor debe mostrar el pop-up del aviso vigente".
 *
 * Devuelve el aviso que corresponde mostrar como pop-up: el obligatorio más
 * reciente que ya se publicó, no expiró y no se confirmó localmente. Si no hay
 * ninguno obligatorio pendiente, devuelve null (no se interrumpe al usuario).
 *
 * Es un Flow: la UI se actualiza sola cuando la sincronización trae un aviso
 * nuevo o cuando el productor confirma el actual.
 */
class ObtenerAvisoActivoUseCase(
    private val avisos: AvisoRepository,
    private val reloj: Reloj,
) {
    operator fun invoke(): Flow<Aviso?> =
        avisos.observarActivos().map { lista ->
            val ahora = reloj.ahora()
            lista.asSequence()
                .filter { it.estaActivo(ahora) && it.obligatorio && !it.vistoLocalmente }
                .sortedByDescending { it.fechaPublicacion }
                .firstOrNull()
        }
}
