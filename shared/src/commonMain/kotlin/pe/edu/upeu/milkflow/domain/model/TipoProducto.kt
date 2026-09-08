package pe.edu.upeu.milkflow.domain.model

enum class TipoProducto {
    QUESO_PARIA_FRESCO,
    QUESO_PARIA_PASTEURIZADO,
    MODULADO,
    YOGURT;

    fun esQueso(): Boolean = this == QUESO_PARIA_FRESCO || this == QUESO_PARIA_PASTEURIZADO || this == MODULADO

    fun nombreVisible(): String = when (this) {
        QUESO_PARIA_FRESCO -> "Queso paria fresco"
        QUESO_PARIA_PASTEURIZADO -> "Queso paria pasteurizado"
        MODULADO -> "Modulado"
        YOGURT -> "Yogurt"
    }
}
