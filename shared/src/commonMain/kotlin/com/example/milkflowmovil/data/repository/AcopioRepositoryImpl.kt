package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.data.local.nuevoUuid
import com.example.milkflowmovil.domain.model.Entrega
import com.example.milkflowmovil.domain.model.Ruta
import com.example.milkflowmovil.domain.repository.AcopioRepository
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

/** Acopio en ruta: abre la ruta del día, registra litros por productor y cierra la ruta. */
class AcopioRepositoryImpl(
    private val cola: ColaOperaciones,
) : AcopioRepository {

    private val base get() = cola.base
    private val yo get() = cola.yo

    /** Ruta de hoy del acopiador; si no existe, se abre una provisional local. */
    override fun rutaDeHoy(): Ruta? {
        val usuario = yo ?: return null
        return base.actual.rutas.firstOrNull { it.date == Fechas.hoy() && it.acopiadorId == usuario.id }
    }

    override fun abrirRutaDeHoy(): Ruta? {
        val usuario = yo ?: return null
        rutaDeHoy()?.let { return it }

        val uuid = nuevoUuid()
        val idTemporal = base.reservarIdTemporal()

        // La zona definitiva la decide el servidor (evita que dos acopiadores
        // tomen la misma zona); aquí se muestra la del padrón como referencia.
        val ruta = Ruta(
            id = idTemporal,
            clientUuid = uuid,
            date = Fechas.hoy(),
            zonaId = usuario.zonaId ?: base.actual.zonas.firstOrNull()?.id ?: 0,
            acopiadorId = usuario.id,
            horaInicio = "04:30:00",
            status = "asignada",
            litrosTotales = 0.0,
            pendiente = true,
        )

        base.actualizar { it.copy(rutas = it.rutas + ruta) }

        cola.encolar(
            uuid, "abrir_ruta",
            buildJsonObject { put("fecha", Fechas.hoy()) },
            "Apertura de ruta del ${Fechas.corta(Fechas.hoy())}",
        )

        return ruta
    }

    override fun registrarEntrega(productorId: Long, litros: Double, notas: String?) {
        val ruta = rutaDeHoy() ?: abrirRutaDeHoy() ?: return
        val productor = base.actual.usuario(productorId)
        val uuid = nuevoUuid()
        val hora = Fechas.horaActual()
        val idNuevo = base.reservarIdTemporal()

        base.actualizar { estado ->
            val previa = estado.entregas.firstOrNull {
                it.rutaId == ruta.id && it.productorId == productorId
            }

            val entrega = (previa ?: Entrega(id = idNuevo, rutaId = ruta.id)).copy(
                clientUuid = uuid,
                productorId = productorId,
                liters = litros,
                hora = hora,
                notes = notas,
                pendiente = true,
                rutaClientUuid = ruta.clientUuid,
            )

            val entregas = estado.entregas.filterNot { it.id == entrega.id } + entrega
            val totalRuta = entregas.filter { it.rutaId == ruta.id }.sumOf { it.liters }

            estado.copy(
                entregas = entregas,
                rutas = estado.rutas.map {
                    if (it.id == ruta.id) {
                        it.copy(
                            litrosTotales = totalRuta,
                            status = if (it.status == "asignada") "en_ruta" else it.status,
                        )
                    } else {
                        it
                    }
                },
            )
        }

        cola.encolar(
            uuid, "registrar_entrega",
            buildJsonObject {
                ruta.clientUuid?.let { put("ruta_client_uuid", it) }
                if (ruta.id > 0) put("ruta_id", ruta.id)
                put("fecha", ruta.date)
                put("producer_id", productorId)
                put("liters", litros)
                put("collected_at", hora)
                notas?.takeIf { it.isNotBlank() }?.let { put("notes", it) }
            },
            "Entrega de ${litros} L de ${productor?.name ?: "productor"}",
        )
    }

    override fun cerrarRuta() {
        val ruta = rutaDeHoy() ?: return
        val uuid = nuevoUuid()

        base.actualizar { estado ->
            estado.copy(rutas = estado.rutas.map {
                if (it.id == ruta.id) it.copy(status = "descargada_planta") else it
            })
        }

        cola.encolar(
            uuid, "cerrar_ruta",
            buildJsonObject {
                ruta.clientUuid?.let { put("ruta_client_uuid", it) }
                if (ruta.id > 0) put("ruta_id", ruta.id)
                put("fecha", ruta.date)
            },
            "Cierre de ruta del ${Fechas.corta(ruta.date)}",
        )
    }
}
