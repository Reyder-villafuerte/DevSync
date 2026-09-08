package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.LoteProduccion
import pe.edu.upeu.milkflow.domain.repository.LoteProduccionRepository

class RegistrarLoteProduccion(
    private val repository: LoteProduccionRepository,
) {
    suspend operator fun invoke(lote: LoteProduccion): LoteProduccion {
        require(lote.litrosLecheUtilizados > 0) { "Los litros de leche deben ser mayores a cero." }
        if (lote.tipoProducto.esQueso()) {
            require(lote.moldesObtenidos > 0) { "Los moldes obtenidos deben ser mayores a cero para queso." }
        }
        return repository.guardar(lote)
    }
}
