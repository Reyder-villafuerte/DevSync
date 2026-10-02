package com.example.milkflowmovil.data.remote

import io.ktor.client.engine.HttpClientEngine

/**
 * Motor de red de cada plataforma.
 *
 * - androidMain: OkHttp (ktor-client-okhttp)
 * - iosMain: Darwin, es decir NSURLSession (ktor-client-darwin)
 *
 * El código común nunca importa OkHttp ni Darwin: solo pide un HttpClientEngine.
 */
expect fun crearMotorHttp(): HttpClientEngine
