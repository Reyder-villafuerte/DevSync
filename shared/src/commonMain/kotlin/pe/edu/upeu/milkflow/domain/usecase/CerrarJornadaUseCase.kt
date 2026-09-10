package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.GeneradorId
import pe.edu.upeu.milkflow.core.Reloj
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Recepcion
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.vo.Litros

/**
 * Intención: "el acopiador cierra la ruta y descarga en tina".
 *
 * Las recolecciones del día ya se encolaron una a una al registrarse; aquí solo
 * se actualiza la cabecera (estado + hora de cierre + litros declarados = suma
 * de las recolecciones locales) y se inserta la recepción. Ambas escrituras
 * pasan por el outbox; NO se llama a la red.
 */
class CerrarJornadaUseCase(
    private val jornadas: JornadaRepository,
    private val recolecciones: RecoleccionRepository,
    private val generadorId: GeneradorId,
    private val reloj: Reloj,
) {
    data class Entrada(
        val jornadaId: String,
        val tina: String? = null,
        val litrosDescargados: Double? = null,
        val recibidoPor: String? = null,
    )

    suspend operator fun invoke(entrada: Entrada): Resultado<JornadaRuta> {
        val jornada = jornadas.porId(entrada.jornadaId)
            ?: return Resultado.Fallo(ErrorApp.Validacion("Jornada", "no encontrada"))
        if (jornada.estado == EstadoJornada.CONCILIADA) {
            return Resultado.Fallo(ErrorApp.ReglaNegocio("JORNADA_CONCILIADA", "La jornada ya fue conciliada en planta."))
        }

        val ahora = reloj.ahora()
        val totalRecolectado = recolecciones.totalLitros(jornada.id)

        val recepcion: Recepcion? = entrada.litrosDescargados?.let { litros ->
            when (val l = Litros.de(litros)) {
                is Resultado.Fallo -> return l
                is Resultado.Exito -> Recepcion(
                    id = generadorId.nuevo(),
                    jornadaId = jornada.id,
                    tina = entrada.tina,
                    litrosDescargados = l.valor,
                    horaDescarga = ahora,
                    recibidoPor = entrada.recibidoPor,
                    updatedAt = ahora,
                    version = 0,
                    deleted = false,
                )
            }
        }

        val jornadaCerrada = jornada.copy(
            estado = if (recepcion != null) EstadoJornada.DESCARGADA else EstadoJornada.CERRADA,
            horaCierre = ahora,
            litrosDeclarados = totalRecolectado,
            updatedAt = ahora,
        )

        return jornadas.cerrar(jornadaCerrada, recepcion)
    }
}
