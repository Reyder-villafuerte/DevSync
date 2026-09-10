package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.calidad.EvaluadorCalidad
import pe.edu.upeu.milkflow.domain.model.Inspeccion
import pe.edu.upeu.milkflow.domain.repository.InspeccionRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.vo.MedicionLactoscan

/**
 * Intención: "el supervisor toma una muestra con el Lactoscan y necesita el
 * dictamen AHORA, esté o no con red".
 *
 * Evalúa RN-05 / RN-06 en el dispositivo con [EvaluadorCalidad], persiste la
 * inspección con su dictamen ya congelado y la encola. El backend re-evaluará
 * al recibirla (su dictamen es el autoritativo), pero en la práctica coincide.
 */
class EvaluarCalidadUseCase(
    private val productores: ProductorRepository,
    private val sanciones: SancionRepository,
    private val inspecciones: InspeccionRepository,
    private val evaluador: EvaluadorCalidad,
    private val generadorId: GeneradorId,
    private val reloj: Reloj,
) {
    data class Entrada(
        val productorId: String,
        val supervisorId: String,
        val medicion: MedicionLactoscan,
        val jornadaId: String? = null,
        val recoleccionId: String? = null,
    )

    suspend operator fun invoke(entrada: Entrada): Resultado<Inspeccion> {
        if (!entrada.medicion.tieneDatosSuficientes) {
            return Resultado.Fallo(ErrorApp.Validacion("Medición", "ingrese al menos agua añadida o pH"))
        }
        val productor = productores.porId(entrada.productorId)
            ?: return Resultado.Fallo(ErrorApp.Validacion("Productor", "no encontrado en el padrón local"))

        // Reincidencia RN-05 a partir de sanciones ya sincronizadas en local.
        val reincide = sanciones.tieneSancionAguaVigente(productor.id)
        val veredicto = evaluador.evaluar(entrada.medicion, reincide)

        val ahora = reloj.ahora()
        val inspeccion = Inspeccion(
            id = generadorId.nuevo(),
            productorId = productor.id,
            supervisorId = entrada.supervisorId,
            jornadaId = entrada.jornadaId,
            recoleccionId = entrada.recoleccionId,
            tomadoEn = ahora,
            medicion = entrada.medicion,
            dictamen = veredicto.dictamen,
            dictamenDetalle = veredicto.detalle,
            esReincidencia = veredicto.esReincidencia,
            updatedAt = ahora,
            version = 0,
            deleted = false,
        )
        return inspecciones.registrar(inspeccion)
    }
}
