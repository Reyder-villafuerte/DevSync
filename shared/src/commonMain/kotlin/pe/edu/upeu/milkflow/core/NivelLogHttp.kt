package pe.edu.upeu.milkflow.core

/**
 * Verbosidad del log HTTP expresada sin exponer tipos de Ktor en la API
 * pública del módulo (los consumidores no deben tener que depender de Ktor).
 * `construirClienteMilkFlow` la traduce a `LogLevel` internamente.
 */
enum class NivelLogHttp { NINGUNO, BASICO, TODO }
