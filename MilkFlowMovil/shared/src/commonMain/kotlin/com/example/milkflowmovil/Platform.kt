package com.example.milkflowmovil

interface Platform {
    val name: String
}

expect fun getPlatform(): Platform