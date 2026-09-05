package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.EstadoSincronizacion
import pe.edu.upeu.milkflow.domain.model.ResultadoSincronizacion
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository

class SincronizarRegistrosPendientes(
    private val sincronizacionRepository: SincronizacionRepository,
) {
    suspend operator fun invoke(): ResultadoSincronizacion {
        val pendientes = sincronizacionRepository.obtenerPendientes()
        var enviados = 0
        var errores = 0

        pendientes.forEach { registro ->
            when (sincronizacionRepository.sincronizar(registro)) {
                EstadoSincronizacion.ENVIADO -> enviados += 1
                EstadoSincronizacion.ERROR -> errores += 1
                EstadoSincronizacion.PENDIENTE -> Unit
            }
        }

        return ResultadoSincronizacion(
            total = pendientes.size,
            enviados = enviados,
            errores = errores,
        )
    }
}
