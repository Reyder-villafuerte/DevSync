package pe.edu.upeu.milkflow.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.Ambito
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.vo.Dni

/** Sesión del usuario autenticado. El token se guarda en almacenamiento seguro. */
data class SesionActiva(
    val usuarioId: String,
    val nombreCompleto: String,
    val rol: RolUsuario,
    val ambito: Ambito,
    val dispositivoId: String,
)

interface SesionRepository {
    fun observarSesion(): Flow<SesionActiva?>
    suspend fun sesionActual(): SesionActiva?

    /** Autentica contra el backend y persiste token + sesión. Requiere red. */
    suspend fun iniciarSesion(dni: Dni, password: String, identificadorDispositivo: String): Resultado<SesionActiva>

    /** Revoca el token en el backend (best-effort) y limpia el estado local. */
    suspend fun cerrarSesion(): Resultado<Unit>

    /** Token Bearer actual, para el plugin de Auth de Ktor. */
    suspend fun tokenActual(): String?
}
