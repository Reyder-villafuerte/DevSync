package com.example.milkflowmovil.data.remote

import io.ktor.client.engine.HttpClientEngine
import io.ktor.client.engine.okhttp.OkHttp

/** Android usa OkHttp, la pila de red estándar de la plataforma. */
actual fun crearMotorHttp(): HttpClientEngine = OkHttp.create()
