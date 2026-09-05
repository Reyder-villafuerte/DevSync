package com.example.holamundo.data.local.enums

/**
 * Estado de sincronización para todas las entidades transaccionales.
 *
 * Estrategia Offline-First:
 * - [PENDING]  El registro fue creado localmente y aún no fue enviado al servidor.
 * - [SYNCED]   El servidor confirmó la recepción y persistencia correcta.
 * - [FAILED]   El intento de sincronización falló (conflicto de datos, error de red, etc.).
 *
 * Flujo: PENDING → SYNCED
 *                → FAILED → (reintento) → SYNCED
 */
enum class SyncStatus {
    /** Registro nuevo, pendiente de enviar al servidor. Estado por defecto. */
    PENDING,

    /** El servidor confirmó la sincronización exitosa. */
    SYNCED,

    /** La sincronización falló. Se reintentará en el siguiente ciclo de WorkManager. */
    FAILED
}
