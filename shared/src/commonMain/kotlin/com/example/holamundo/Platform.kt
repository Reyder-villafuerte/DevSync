package com.example.holamundo

interface Platform {
    val name: String
}

expect fun getPlatform(): Platform