package pe.edu.upeu.milkflow.domain.usecase

import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository

class RegistrarAcopiador(
    private val acopiadorRepository: AcopiadorRepository,
) {
    suspend operator fun invoke(acopiador: Acopiador): Acopiador =
        acopiadorRepository.guardar(acopiador)
}
