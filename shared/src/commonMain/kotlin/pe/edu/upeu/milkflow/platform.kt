package pe.edu.upeu.milkflow

interface Platform {
    val name: String
}

expect fun getPlatform(): Platform
