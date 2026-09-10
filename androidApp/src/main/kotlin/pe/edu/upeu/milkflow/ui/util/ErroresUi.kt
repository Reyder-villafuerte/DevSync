package pe.edu.upeu.milkflow.ui.util

import pe.edu.upeu.milkflow.core.ErrorApp

/**
 * Mensaje en español listo para mostrar. Todo `ErrorApp` ya trae `.mensaje`;
 * aquí sólo se afinan los casos que la UI diferencia (validación por campo,
 * sesión expirada).
 */
fun ErrorApp.mensajeUi(): String = when (this) {
    is ErrorApp.Validacion -> "$campo: $motivo"
    is ErrorApp.ReglaNegocio -> detalle
    ErrorApp.NoAutorizado -> "Su sesión expiró. Vuelva a iniciar sesión."
    ErrorApp.Prohibido -> "Su rol no tiene permiso para esta acción."
    is ErrorApp.SinRed -> "Sin conexión. El cambio se guardó y se enviará al sincronizar."
    else -> mensaje
}
