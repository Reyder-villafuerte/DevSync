package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository

/**
 * Intención: "el acopiador cierra la ruta".
 *
 * Las recolecciones del día ya se encolaron una a una al registrarse; aquí solo
 * se actualiza la cabecera (estado + hora de cierre + litros declarados = suma
 * de las recolecciones locales). La escritura pasa por el outbox; NO se llama a
 * la red.
 */
class CerrarJornadaUseCase(
    private val jornadas: JornadaRepository,
    private val recolecciones: RecoleccionRepository,
    private val reloj: Reloj,
) {
    data class Entrada(
        val jornadaId: String,
    )

    suspend operator fun invoke(entrada: Entrada): Resultado<JornadaRuta> {
        val jornada = jornadas.porId(entrada.jornadaId)
            ?: return Resultado.Fallo(ErrorApp.Validacion("Jornada", "no encontrada"))
        if (jornada.estado == EstadoJornada.CONCILIADA) {
            return Resultado.Fallo(ErrorApp.ReglaNegocio("JORNADA_CONCILIADA", "La jornada ya fue conciliada en planta."))
        }

        val ahora = reloj.ahora()
        val totalRecolectado = recolecciones.totalLitros(jornada.id)

        val jornadaCerrada = jornada.copy(
            estado = EstadoJornada.CERRADA,
            horaCierre = ahora,
            litrosDeclarados = totalRecolectado,
            updatedAt = ahora,
        )

        return jornadas.cerrar(jornadaCerrada)
    }
}
