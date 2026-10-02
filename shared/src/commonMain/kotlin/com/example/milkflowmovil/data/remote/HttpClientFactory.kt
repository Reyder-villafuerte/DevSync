package com.example.milkflowmovil.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.engine.HttpClientEngine
import io.ktor.client.plugins.HttpTimeout
import io.ktor.client.plugins.contentnegotiation.ContentNegotiation
import io.ktor.client.plugins.defaultRequest
import io.ktor.client.plugins.logging.LogLevel
import io.ktor.client.plugins.logging.Logger
import io.ktor.client.plugins.logging.Logging
import io.ktor.client.plugins.logging.SIMPLE
import io.ktor.client.request.accept
import io.ktor.http.ContentType
import io.ktor.serialization.kotlinx.json.json
import kotlinx.serialization.json.Json

/**
 * Json compartido por la API y por la base local del teléfono.
 *
 * - ignoreUnknownKeys: si el servidor agrega columnas, la app no se cae.
 * - explicitNulls = false: un campo nulo puede omitirse en el JSON.
 * - isLenient: acepta números que llegan como texto desde MySQL.
 * - encodeDefaults: al subir, se envían también los valores por defecto.
 */
val jsonMilkFlow: Json = Json {
    ignoreUnknownKeys = true
    explicitNulls = false
    isLenient = true
    encodeDefaults = true
}

/**
 * Fábrica del HttpClient en commonMain.
 *
 * Recibe el motor como parámetro: OkHttp en Android y Darwin en iOS
 * (ver `MotorHttp.kt`). Este archivo no depende de ninguna plataforma.
 *
 * @param engine motor de red de la plataforma.
 * @param urlBase dirección del servidor de la planta. Es una función porque el
 *   usuario puede cambiarla en el login (la IP de la planta no es fija).
 */
fun createHttpClient(engine: HttpClientEngine, urlBase: () -> String): HttpClient =
    HttpClient(engine) {
        // Por defecto Ktor NO lanza excepción ante 4xx/5xx: se revisa
        // response.status en MilkFlowRemoteDataSource.
        expectSuccess = false

        install(ContentNegotiation) {   // JSON <-> Kotlin
            json(jsonMilkFlow)
        }

        install(Logging) {   // traza en consola (método, URL y código)
            logger = Logger.SIMPLE
            level = LogLevel.INFO
        }

        install(HttpTimeout) {   // milisegundos
            connectTimeoutMillis = 10_000
            requestTimeoutMillis = 15_000
            socketTimeoutMillis = 15_000
        }

        defaultRequest {   // común a toda petición
            url(urlBase().trim().trimEnd('/') + "/api/")
            accept(ContentType.Application.Json)
        }
    }
