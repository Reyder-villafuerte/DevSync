package com.example.milkflowmovil.data.remote

import io.ktor.client.engine.HttpClientEngine
import io.ktor.client.engine.darwin.Darwin

/** iOS usa Darwin, que delega en NSURLSession de Apple. */
actual fun crearMotorHttp(): HttpClientEngine = Darwin.create()
