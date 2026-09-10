package pe.edu.upeu.milkflow.data.remote

import kotlin.time.Clock
import kotlin.time.Instant
import kotlinx.serialization.json.jsonObject
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.data.remote.dto.OperacionPushDto
import pe.edu.upeu.milkflow.data.remote.dto.PushPeticionDto
import pe.edu.upeu.milkflow.data.remote.dto.PushRespuestaDto
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.sync.ClienteSincronizacion
import pe.edu.upeu.milkflow.domain.sync.Entidades
import pe.edu.upeu.milkflow.domain.sync.OperacionAceptada
import pe.edu.upeu.milkflow.domain.sync.OperacionEnConflicto
import pe.edu.upeu.milkflow.domain.sync.OperacionOutbox
import pe.edu.upeu.milkflow.domain.sync.OperacionRechazada
import pe.edu.upeu.milkflow.domain.sync.ResultadoSubida
import pe.edu.upeu.milkflow.domain.sync.ResumenBajada
import pe.edu.upeu.milkflow.domain.sync.TipoOperacion

/**
 * Implementación de la pasarela de sincronización sobre Ktor.
 *
 * SUBIDA: casi todo va en una sola llamada a /api/sync/push. `avisos_vistos`
 * usa su endpoint dedicado (POST /api/avisos/{id}/visto) porque el backend no
 * lo expone como entidad de push.
 *
 * BAJADA: pide la página, la aplica a la BD local en una transacción
 * (AplicadorCambios) y devuelve solo el resumen (cursor, hayMas).
 */
class ClienteSincronizacionHttp(
    private val api: ApiMilkFlow,
    private val aplicador: AplicadorCambios,
) : ClienteSincronizacion {

    override suspend fun subir(operaciones: List<OperacionOutbox>): Resultado<ResultadoSubida> {
        val (avisos, genericas) = operaciones.partition { it.tabla == Entidades.AVISO_VISTO }

        val aceptadas = mutableListOf<OperacionAceptada>()
        val conflictos = mutableListOf<OperacionEnConflicto>()
        val rechazadas = mutableListOf<OperacionRechazada>()

        if (genericas.isNotEmpty()) {
            val dto = PushPeticionDto(genericas.map { it.aDto() })
            when (val r = api.push(dto)) {
                is Resultado.Fallo -> return r  // nada entró: reintento completo
                is Resultado.Exito -> volcar(r.valor, aceptadas, conflictos, rechazadas)
            }
        }

        // Endpoint dedicado para los acuses de aviso. Si falla el transporte se
        // deja sin mencionar: el SyncManager lo contará como intento fallido.
        for (op in avisos) {
            if (api.marcarAvisoVisto(op.idRegistro) is Resultado.Exito) {
                aceptadas += OperacionAceptada(op.idRegistro, op.tabla, 0, "insertado")
            }
        }

        return Resultado.Exito(ResultadoSubida(aceptadas, conflictos, rechazadas))
    }

    override suspend fun bajar(desde: String?, ambito: Ambito): Resultado<ResumenBajada> =
        when (val r = api.pull(desde, ambito.parametro)) {
            is Resultado.Fallo -> r
            is Resultado.Exito -> {
                val aplicadas = aplicador.aplicar(r.valor.cambios)
                Resultado.Exito(
                    ResumenBajada(
                        cursor = r.valor.cursor,
                        hayMas = r.valor.hayMas,
                        servidorEn = runCatching { Instant.parse(r.valor.servidorEn) }.getOrElse { Clock.System.now() },
                        filasAplicadas = aplicadas,
                    ),
                )
            }
        }

    private fun OperacionOutbox.aDto() = OperacionPushDto(
        entidad = tabla,
        id = idRegistro,
        versionBase = versionBase,
        atributos = jsonMilkFlow.parseToJsonElement(payloadJson).jsonObject,
        eliminar = operacion == TipoOperacion.ELIMINAR,
    )

    private fun volcar(
        dto: PushRespuestaDto,
        aceptadas: MutableList<OperacionAceptada>,
        conflictos: MutableList<OperacionEnConflicto>,
        rechazadas: MutableList<OperacionRechazada>,
    ) {
        dto.aceptadas.forEach { aceptadas += OperacionAceptada(it.id, it.entidad, it.version, it.resultado) }
        dto.conflictos.forEach { conflictos += OperacionEnConflicto(it.id, it.entidad, it.motivo, it.servidor?.toString()) }
        dto.rechazadas.forEach { rechazadas += OperacionRechazada(it.id, it.entidad ?: "?", it.motivo) }
    }
}
