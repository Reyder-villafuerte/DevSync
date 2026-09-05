package pe.edu.upeu.milkflow.domain.model

data class RegistroSincronizable(
    val registroId: String,
    val tipoRegistro: String,
    val estado: EstadoSincronizacion,
) {
    init {
        require(registroId.isNotBlank()) { "El identificador del registro es obligatorio." }
        require(tipoRegistro.isNotBlank()) { "El tipo de registro es obligatorio." }
    }
}

data class ResultadoSincronizacion(
    val total: Int,
    val enviados: Int,
    val errores: Int,
)
