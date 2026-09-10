package pe.edu.upeu.milkflow.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Aviso
import pe.edu.upeu.milkflow.domain.model.Inspeccion
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.model.Liquidacion
import pe.edu.upeu.milkflow.domain.model.Precio
import pe.edu.upeu.milkflow.domain.model.Productor
import pe.edu.upeu.milkflow.domain.model.Recepcion
import pe.edu.upeu.milkflow.domain.model.Recoleccion
import pe.edu.upeu.milkflow.domain.model.Ruta
import pe.edu.upeu.milkflow.domain.model.Sancion
import pe.edu.upeu.milkflow.domain.model.SolicitudRuta
import pe.edu.upeu.milkflow.domain.model.Zona
import pe.edu.upeu.milkflow.domain.vo.Litros

/*
 * Interfaces de repositorio. NO dependen de SQLDelight ni de Ktor.
 *
 * Regla transversal de todas las implementaciones (capa data):
 *   toda escritura de usuario se persiste en SQLite Y encola su operación en
 *   la tabla `outbox` DENTRO DE LA MISMA TRANSACCIÓN. Ninguna devuelve un
 *   Fallo por falta de red: la red es asunto del SyncManager.
 */

interface ProductorRepository {
    fun observarPorZona(zonaId: String): Flow<List<Productor>>
    fun observarPadron(): Flow<List<Productor>>
    suspend fun porId(id: String): Productor?
}

interface ZonaRepository {
    fun observarTodas(): Flow<List<Zona>>
    suspend fun porId(id: String): Zona?
}

interface RutaRepository {
    fun observarTodas(): Flow<List<Ruta>>
    suspend fun porId(id: String): Ruta?
}

interface JornadaRepository {
    /** La jornada en curso del acopiador para hoy, si existe. */
    fun observarJornadaActiva(acopiadorId: String): Flow<JornadaRuta?>
    suspend fun jornadaActiva(acopiadorId: String): JornadaRuta?
    suspend fun porId(id: String): JornadaRuta?

    /** Crea la cabecera (local + outbox). */
    suspend fun iniciar(acopiadorId: String, rutaId: String): Resultado<JornadaRuta>

    /** Marca la jornada como cerrada/descargada (local + outbox). */
    suspend fun cerrar(jornada: JornadaRuta, recepcion: Recepcion?): Resultado<JornadaRuta>
}

interface RecoleccionRepository {
    fun observarPorJornada(jornadaId: String): Flow<List<Recoleccion>>
    fun observarTodas(): Flow<List<Recoleccion>>

    /** Entregas de un productor, más recientes primero. Incluye el estado de recepción. */
    fun observarPorProductor(productorId: String): Flow<List<Recoleccion>>

    /** Persiste la recolección y encola su inserción. Solo inserción. */
    suspend fun registrar(recoleccion: Recoleccion): Resultado<Recoleccion>

    suspend fun yaRegistrada(jornadaId: String, productorId: String): Boolean

    /** Suma de litros de todas las recolecciones vivas de la jornada. */
    suspend fun totalLitros(jornadaId: String): Litros
}

interface RecepcionRepository {
    suspend fun registrar(recepcion: Recepcion): Resultado<Recepcion>
    fun observarPorJornada(jornadaId: String): Flow<Recepcion?>
}

interface InspeccionRepository {
    fun observarPorProductor(productorId: String): Flow<List<Inspeccion>>

    /** Persiste la inspección con su dictamen ya calculado. Solo inserción. */
    suspend fun registrar(inspeccion: Inspeccion): Resultado<Inspeccion>
}

interface SancionRepository {
    fun observarPorProductor(productorId: String): Flow<List<Sancion>>

    /** ¿El productor arrastra una sanción de agua vigente? Base de la reincidencia RN-05. */
    suspend fun tieneSancionAguaVigente(productorId: String): Boolean
}

interface AvisoRepository {
    fun observarActivos(): Flow<List<Aviso>>
    suspend fun marcarVisto(avisoId: String): Resultado<Unit>
}

interface SolicitudRutaRepository {
    fun observarMisSolicitudes(productorId: String): Flow<List<SolicitudRuta>>
    suspend fun crear(solicitud: SolicitudRuta): Resultado<SolicitudRuta>
}

interface PrecioRepository {
    fun observarVigentes(): Flow<List<Precio>>
    suspend fun tarifaCompraVigente(): Precio?
}

interface LiquidacionRepository {
    fun observarPorProductor(productorId: String): Flow<List<Liquidacion>>
}
