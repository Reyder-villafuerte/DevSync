package pe.edu.upeu.milkflow.domain.usecase

import kotlinx.coroutines.flow.Flow
import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.model.RangoFechas
import pe.edu.upeu.milkflow.domain.repository.LoteProduccionRepository

class ObtenerLotesProduccion(
    private val repository: LoteProduccionRepository,
) {
    suspend operator fun invoke(rango: RangoFechas? = null): List<LoteProduccion> {
        return if (rango != null) {
            repository.obtenerPorRango(rango)
        } else {
            // Sin flow por ahora para coincidir con el resto de usecases de consulta si no se especifica flow
            // Revisando ObtenerEntregas.kt veo que devuelve List o Flow?
            // Mirando SincronizacionRepository.kt tiene observarRegistros: Flow<List<...>>
            // Vamos a devolver List por ahora para invoke(rango)
            emptyList() // Placeholder hasta que implemente List completa
        }
    }

    fun observarTodos(): Flow<List<LoteProduccion>> = repository.observarLotes()
}
