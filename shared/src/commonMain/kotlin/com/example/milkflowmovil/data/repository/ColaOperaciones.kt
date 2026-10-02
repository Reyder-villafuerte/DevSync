package com.example.milkflowmovil.data.repository

import com.example.milkflowmovil.core.Fechas
import com.example.milkflowmovil.data.local.BaseLocal
import com.example.milkflowmovil.data.sync.Sincronizador
import com.example.milkflowmovil.domain.model.EstadoApp
import com.example.milkflowmovil.domain.model.OperacionPendiente
import com.example.milkflowmovil.domain.model.Stock
import com.example.milkflowmovil.domain.model.Usuario
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.launch
import kotlinx.serialization.json.JsonObject

/**
 * Pieza compartida por todas las implementaciones de repositorio.
 *
 * Regla del proyecto: ninguna pantalla habla con la red. Cada acción escribe
 * de inmediato en la base local (el usuario ve el resultado al instante, con o
 * sin señal) y deja aquí la operación en cola para el servidor.
 */
class ColaOperaciones(
    val base: BaseLocal,
    private val sincronizador: Sincronizador,
    private val alcance: CoroutineScope,
) {
    /** Usuario con la sesión iniciada. */
    val yo: Usuario? get() = base.actual.sesion?.usuario

    fun encolar(uuid: String, comando: String, payload: JsonObject, descripcion: String) {
        base.actualizar { estado ->
            estado.copy(
                cola = estado.cola + OperacionPendiente(
                    clientUuid = uuid,
                    comando = comando,
                    payload = payload.toString(),
                    descripcion = descripcion,
                    creadaEn = Fechas.ahoraIso(),
                )
            )
        }
        sincronizarEnSegundoPlano()
    }

    /**
     * La sincronización se lanza en el ámbito de la app, no en el de la
     * pantalla: si el usuario cambia de pantalla, la subida no se cancela.
     */
    fun sincronizarEnSegundoPlano() {
        alcance.launch { sincronizador.sincronizar() }
    }
}

private const val ID_STOCK_LECHE = -9001L
private const val ID_STOCK_QUESO = -9002L

/**
 * Ajusta el stock local. Si la fila aún no bajó del servidor se crea con un
 * id provisional fijo por código; la bajada la reemplaza por código, no por id.
 */
internal fun ajustarStock(estado: EstadoApp, codigo: String, delta: Double): List<Stock> {
    val existente = estado.stocks.firstOrNull { it.codigo == codigo }
        ?: Stock(
            id = if (codigo == Stock.LECHE) ID_STOCK_LECHE else ID_STOCK_QUESO,
            codigo = codigo,
            nombre = if (codigo == Stock.LECHE) "Leche fresca" else "Moldes de queso",
            unit = if (codigo == Stock.LECHE) "litros" else "moldes",
        )

    val actualizado = existente.copy(cantidad = maxOf(0.0, existente.cantidad + delta))
    return estado.stocks.filterNot { it.codigo == codigo } + actualizado
}
