package pe.edu.upeu.milkflow.domain.model

/**
 * Ámbito de sincronización. Determina qué subconjunto de datos baja el móvil
 * (el acopiador de la Ruta 01 recibe su padrón, no las ventas de planta).
 * Se serializa como `tipo:valor` para el parámetro ?ambito= del backend.
 */
sealed interface Ambito {
    val parametro: String

    data class Ruta(val codigo: String) : Ambito {
        override val parametro get() = "ruta:$codigo"
    }

    data class Zona(val codigo: String) : Ambito {
        override val parametro get() = "zona:$codigo"
    }

    data class Productor(val id: String) : Ambito {
        override val parametro get() = "productor:$id"
    }

    data object Global : Ambito {
        override val parametro get() = "global"
    }

    companion object {
        fun desde(parametro: String?): Ambito {
            if (parametro.isNullOrBlank() || parametro == "global") return Global
            val (tipo, valor) = parametro.split(":", limit = 2).let {
                it[0] to it.getOrElse(1) { "" }
            }
            return when (tipo) {
                "ruta" -> Ruta(valor)
                "zona" -> Zona(valor)
                "productor" -> Productor(valor)
                else -> Global
            }
        }
    }
}
