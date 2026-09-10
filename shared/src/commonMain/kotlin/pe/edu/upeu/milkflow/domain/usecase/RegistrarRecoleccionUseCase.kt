package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.core.flatMap
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.vo.Litros

/**
 * Intención: "el acopiador registra los litros que entregó un productor".
 *
 * Nunca requiere red: valida contra datos locales, persiste en SQLite y encola
 * en el outbox (todo dentro de la transacción del repositorio).
 */
class RegistrarRecoleccionUseCase(
    private val jornadas: JornadaRepository,
    private val productores: ProductorRepository,
    private val recolecciones: RecoleccionRepository,
    private val generadorId: GeneradorId,
    private val reloj: Reloj,
) {
    data class Entrada(
        val acopiadorId: String,
        val productorId: String,
        val litros: Double,
        val observacion: String? = null,
        val sospechaAdulteracion: Boolean = false,
    )

    suspend operator fun invoke(entrada: Entrada): Resultado<Recoleccion> {
        val jornada = jornadas.jornadaActiva(entrada.acopiadorId)
            ?: return Resultado.Fallo(ErrorApp.Validacion("Jornada", "no hay una ruta abierta; inicie la jornada primero"))
        if (jornada.estado != EstadoJornada.EN_CURSO) {
            return Resultado.Fallo(ErrorApp.Validacion("Jornada", "la ruta ya fue cerrada"))
        }

        val productor = productores.porId(entrada.productorId)
            ?: return Resultado.Fallo(ErrorApp.Validacion("Productor", "no encontrado en el padrón local"))
        if (!productor.estado.puedeEntregar) {
            return Resultado.Fallo(ErrorApp.ReglaNegocio("PRODUCTOR_NO_HABILITADO", "El productor está ${productor.estado.clave} y no puede entregar."))
        }
        if (recolecciones.yaRegistrada(jornada.id, productor.id)) {
            return Resultado.Fallo(ErrorApp.ReglaNegocio("RECOLECCION_DUPLICADA", "Ese productor ya entregó en esta ruta."))
        }

        return Litros.de(entrada.litros).flatMap { litros ->
            val ahora = reloj.ahora()
            val recoleccion = Recoleccion(
                id = generadorId.nuevo(),
                jornadaId = jornada.id,
                productorId = productor.id,
                litros = litros,
                horaRegistro = ahora,
                observacion = entrada.observacion,
                sospechaAdulteracion = entrada.sospechaAdulteracion,
                updatedAt = ahora,   // sello local hasta que el servidor confirme
                version = 0,          // nace local
                deleted = false,
            )
            recolecciones.registrar(recoleccion)
        }
    }
}
