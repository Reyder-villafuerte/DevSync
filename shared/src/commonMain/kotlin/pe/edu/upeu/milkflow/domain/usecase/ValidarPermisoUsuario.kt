package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.AccesoDenegadoException
import pe.edu.upeu.milkflow.domain.model.AccionUsuario
import pe.edu.upeu.milkflow.domain.model.RolUsuario

class ValidarPermisoUsuario {
    operator fun invoke(rol: RolUsuario, accion: AccionUsuario): Boolean = when (rol) {
        RolUsuario.ADMINISTRADORA -> true
        RolUsuario.JEFE_PRODUCCION -> accion in setOf(
            AccionUsuario.REGISTRAR_PRODUCTOR,
            AccionUsuario.ACTUALIZAR_PRODUCTOR,
            AccionUsuario.REGISTRAR_ENTREGA_DIRECTA,
            AccionUsuario.REGISTRAR_LECHE_RECOGIDA,
            AccionUsuario.SINCRONIZAR_REGISTROS,
            AccionUsuario.CONSULTAR_AUDITORIA,
        )
        RolUsuario.ACOPIADOR -> accion in setOf(
            AccionUsuario.REGISTRAR_LECHE_RECOGIDA,
            AccionUsuario.SINCRONIZAR_REGISTROS,
        )
        RolUsuario.SUPERVISOR -> accion in setOf(
            AccionUsuario.REGISTRAR_PRUEBA_CALIDAD,
            AccionUsuario.REGISTRAR_PROBLEMA_CALIDAD,
            AccionUsuario.CONSULTAR_AUDITORIA,
            AccionUsuario.REGISTRAR_ACOPIADOR,
            AccionUsuario.SINCRONIZAR_REGISTROS,
        )
        RolUsuario.DESPACHO_QUESO -> false // Pendiente definir permisos específicos
        RolUsuario.PENDIENTE_ASIGNACION -> false
    }

    fun requerir(rol: RolUsuario, accion: AccionUsuario) {
        if (!invoke(rol, accion)) {
            throw AccesoDenegadoException(rol.name, accion.name)
        }
    }
}
