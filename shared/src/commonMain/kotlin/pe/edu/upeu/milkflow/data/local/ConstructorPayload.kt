package pe.edu.upeu.milkflow.data.local

import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put
import pe.edu.upeu.milkflow.domain.model.Inspeccion
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Recepcion
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.model.SolicitudRuta

/**
 * Construye el objeto `atributos` (camelCase) que espera /api/sync/push para
 * cada entidad. Vive en la capa data: conoce la forma del contrato del backend.
 * Un JsonObject.toString() ya es JSON válido y compacto.
 */
internal object ConstructorPayload {

    fun recoleccion(r: Recoleccion): String = buildJsonObject {
        put("rutaAcopioId", r.jornadaId)
        put("productorId", r.productorId)
        put("litros", r.litros.valor)
        put("horaRegistro", r.horaRegistro.toString())
        r.observacion?.let { put("observacion", it) }
        put("sospechaAdulteracion", r.sospechaAdulteracion)
    }.toString()

    fun recepcion(r: Recepcion): String = buildJsonObject {
        put("rutaAcopioId", r.jornadaId)
        r.tina?.let { put("tina", it) }
        put("litrosDescargados", r.litrosDescargados.valor)
        put("horaDescarga", r.horaDescarga.toString())
        r.recibidoPor?.let { put("recibidoPor", it) }
    }.toString()

    fun jornada(j: JornadaRuta): String = buildJsonObject {
        put("acopiadorId", j.acopiadorId)
        put("rutaId", j.rutaId)
        j.dispositivoId?.let { put("dispositivoId", it) }
        put("fecha", j.fecha.toString())
        put("horaInicio", j.horaInicio.toString())
        j.horaCierre?.let { put("horaCierre", it.toString()) }
        put("litrosDeclarados", j.litrosDeclarados.valor)
        put("estado", j.estado.clave)
    }.toString()

    fun inspeccion(i: Inspeccion): String = buildJsonObject {
        put("productorId", i.productorId)
        put("supervisorId", i.supervisorId)
        i.jornadaId?.let { put("rutaAcopioId", it) }
        i.recoleccionId?.let { put("registroAcopioId", it) }
        put("tomadoEn", i.tomadoEn.toString())
        i.medicion.aguaAnadidaPorcentaje?.let { put("aguaAnadidaPorcentaje", it) }
        i.medicion.ph?.let { put("ph", it) }
        i.medicion.densidad?.let { put("densidad", it) }
        i.medicion.grasaPorcentaje?.let { put("grasaPorcentaje", it) }
        i.medicion.solidosNoGrasosPorcentaje?.let { put("solidosNoGrasosPorcentaje", it) }
        i.medicion.temperatura?.let { put("temperatura", it) }
        put("dictamen", i.dictamen.clave)
    }.toString()

    fun solicitudRuta(s: SolicitudRuta): String = buildJsonObject {
        put("productorId", s.productorId)
        put("zonaActualId", s.zonaActualId)
        put("zonaSolicitadaId", s.zonaSolicitadaId)
        put("motivo", s.motivo)
        put("estado", s.estado.clave)
    }.toString()

    fun avisoVisto(avisoId: String): String = buildJsonObject {
        put("avisoId", avisoId)
    }.toString()
}
