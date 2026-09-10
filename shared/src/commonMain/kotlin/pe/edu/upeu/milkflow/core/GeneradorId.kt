package pe.edu.upeu.milkflow.core

import kotlin.uuid.ExperimentalUuidApi
import kotlin.uuid.Uuid

/**
 * Genera los UUID de las claves primarias EN EL CLIENTE (requisito offline-first:
 * insertar sin red y sin colisiones). Se inyecta como dependencia para poder
 * fijar ids en tests.
 */
fun interface GeneradorId {
    fun nuevo(): String
}

@OptIn(ExperimentalUuidApi::class)
class GeneradorUuid : GeneradorId {
    override fun nuevo(): String = Uuid.random().toString()
}
