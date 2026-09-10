package pe.edu.upeu.milkflow.ui.screens.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.usecase.IniciarSesionUseCase

data class LoginUiState(
    val dni: String = "",
    val password: String = "",
    val cargando: Boolean = false,
    val error: String? = null,
) {
    val puedeIngresar: Boolean get() = dni.isNotBlank() && password.isNotBlank() && !cargando
}

/**
 * Login: única pantalla que exige red (la primera vez). Al obtener token, NO
 * navega a mano: `SesionRepositoryHttp` guarda el token + la fila de sesión, y
 * el `SesionGateViewModel` re-enruta al grafo del rol al observar el cambio.
 */
class LoginViewModel(
    private val iniciarSesion: IniciarSesionUseCase,
    private val idDispositivo: () -> String,
) : ViewModel() {

    private val _estado = MutableStateFlow(LoginUiState())
    val estado: StateFlow<LoginUiState> = _estado.asStateFlow()

    fun onDni(v: String) = _estado.update { it.copy(dni = v.filter(Char::isDigit).take(8), error = null) }
    fun onPassword(v: String) = _estado.update { it.copy(password = v, error = null) }

    fun ingresar() {
        val s = _estado.value
        if (!s.puedeIngresar) return
        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            when (val r = iniciarSesion(s.dni, s.password, idDispositivo())) {
                is Resultado.Exito -> _estado.update { it.copy(cargando = false) }
                is Resultado.Fallo -> _estado.update { it.copy(cargando = false, error = mensajeDe(r.error)) }
            }
        }
    }

    private fun mensajeDe(error: ErrorApp): String = when (error) {
        is ErrorApp.SinRed -> buildString {
            append("Se necesita conexión sólo para el primer inicio de sesión. Conéctese e intente de nuevo.")
            // Detalle técnico del fallo de red (útil para diagnosticar en desarrollo).
            error.causa?.let { c ->
                append("\n\n[detalle: ")
                append(c::class.simpleName)
                c.message?.let { append(": "); append(it) }
                append("]")
            }
        }
        ErrorApp.NoAutorizado, ErrorApp.Prohibido ->
            "DNI o contraseña incorrectos."
        is ErrorApp.Validacion -> "${error.campo}: ${error.motivo}"
        else -> error.mensaje
    }
}
