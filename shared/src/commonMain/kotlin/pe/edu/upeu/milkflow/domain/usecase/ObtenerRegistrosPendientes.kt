package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.domain.model.RegistroSincronizable
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository

class ObtenerRegistrosPendientes(
    private val sincronizacionRepository: SincronizacionRepository,
) {
    operator fun invoke(): Flow<List<RegistroSincronizable>> =
        sincronizacionRepository.observarPendientes()
}
