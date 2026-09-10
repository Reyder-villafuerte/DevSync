# CHANGELOG_BUILD — estabilización del build de `shared/`

Estado final:

| Objetivo | Tarea | Resultado |
|---|---|---|
| commonMain | `:shared:compileKotlinMetadata` | ✅ compila |
| Android | `:shared:compileAndroidMain` / `:shared:assembleAndroidMain` | ✅ compila, AAR generado |
| iOS | `:shared:compileKotlinIosSimulatorArm64` + `:shared:compileTestKotlinIosSimulatorArm64` | ✅ compila (klib) |
| App | `:androidApp:assembleDebug` | ✅ compila |
| Tests | `:shared:allTests` | ✅ **24 tests en verde** (host JVM); `iosSimulatorArm64Test` **SKIPPED** (correcto: requiere macOS + simulador) |

Toolchain usada: Gradle **9.5.0**, AGP **9.1.1**, Kotlin **2.4.10**, JDK 17, Android SDK 37 (todo ya presente en la máquina de build).

Comandos de verificación:

```bash
./gradlew :shared:tasks
./gradlew :shared:compileKotlinMetadata
./gradlew :shared:compileAndroidMain
./gradlew :shared:compileKotlinIosSimulatorArm64
./gradlew :shared:allTests
./gradlew :androidApp:assembleDebug
```

---

## A · Errores de versión / configuración de plugins Gradle

### A1 · `org.jetbrains.kotlin.android` prohibido con AGP 9
- **Qué falló:** `:androidApp` aplicaba `alias(libs.plugins.androidKotlin)` (`org.jetbrains.kotlin.android`). Gradle:
  `The 'org.jetbrains.kotlin.android' plugin is no longer required for Kotlin support since AGP 9.0`.
  Antes de eso: `plugin is already on the classpath with an unknown version`.
- **Causa raíz:** AGP 9.0+ trae **soporte de Kotlin integrado**. Aplicar además el plugin `kotlin.android` es un conflicto de classpath (mismo artefacto que el KGP que ya arrastra `:shared`).
- **Qué se cambió:**
  - Se eliminó el alias `androidKotlin` de `androidApp/build.gradle.kts` y del `build.gradle.kts` raíz.
  - `androidApp` aplica solo `com.android.application` y configura Kotlin con el DSL integrado de AGP:
    `android { kotlin { jvmToolchain(17) } }`.
  - Se quitó `androidKotlin` de `libs.versions.toml` (`[plugins]`).
- **Por qué:** es la forma soportada en AGP 9; no es un parche, es la configuración correcta.

### A2 · Wrapper de Gradle ausente
- **Qué falló:** `gradlew`, `gradlew.bat` y `gradle/wrapper/gradle-wrapper.jar` estaban borrados en el árbol de trabajo (solo quedaba `gradle-wrapper.properties`, con una URL a Gradle 8.13).
- **Qué se cambió:** regenerados con `gradle wrapper --gradle-version 9.5.0 --distribution-type bin`. La 9.5.0 es la que soporta AGP 9.1.1 + Kotlin 2.4.10 (verificado: el build pasa con ella).
- `local.properties` con `sdk.dir` — creado apuntando al SDK ya instalado. (No se versiona.)

### A3 · Versiones — verificación
Todas las versiones de `libs.versions.toml` **existen y son compatibles** (verificado contra Maven Central / Google Maven y confirmado por un build exitoso). No se degradó ninguna. Detalle en la cabecera de `libs.versions.toml`.

---

## B · Errores de API generada por SQLDelight 2.3

Se generó e inspeccionó el código real (`:shared:generateCommonMainMilkFlowDatabaseInterface`,
`build/generated/sqldelight/.../MilkFlowQueries.kt`). Diferencias con lo asumido:

### B1 · Los mutadores devuelven `QueryResult<Long>`, no `Unit`
- **Qué falló:** en `SincronizacionRepositoryLocal`, 7 overrides estaban escritos como
  `override suspend fun descartar(id) = withContext(io) { q.eliminarOutbox(id) }`.
  El cuerpo de expresión hacía que el tipo de retorno inferido fuera `QueryResult<Long>`, que no es
  subtipo del `Unit` declarado en la interfaz de dominio `SincronizacionRepository`. 9 errores
  `Return type ... is not a subtype of the return type of the overridden member`.
- **Causa raíz:** SQLDelight 2.x cambió los INSERT/UPDATE/DELETE generados: ahora devuelven
  `app.cash.sqldelight.db.QueryResult<Long>` (filas afectadas), no `Unit`.
- **Qué se cambió:** cuerpos de **bloque** (sin `=`) en `marcarSincronizada`, `registrarIntentoFallido`,
  `marcarConflicto`, `descartar`, `guardarCursor`, `marcarSincronizando`, `registrarExito`. Dentro,
  `withContext(io) { q.mutation() }` como sentencia; el `QueryResult` se descarta y el override
  respeta `Unit`.
- **No afectó** a los repos de escritura (`RecoleccionRepositoryLocal`, etc.): allí los mutadores
  son sentencias dentro de `db.transaction { }` y su retorno ya se descarta.
- **No es problema de diseño:** el contrato de dominio (`Unit`) es correcto; solo había que no
  filtrar el tipo de SQLDelight por el cuerpo de expresión.

### B2 · `estadoValor` genera un wrapper `EstadoValor`, no `String?`
- **Qué falló:** `SincronizacionRepositoryLocal.observarEstado()` trataba el resultado de
  `q.estadoValor("ultimo_exito")` como `Query<String?>`. Real:
  `Query<EstadoValor>` con `data class EstadoValor(val valor: String?)`.
- **Causa raíz:** SQLDelight genera un tipo de resultado nombrado por la query cuando la proyección
  no corresponde 1:1 a una fila de tabla (aquí `SELECT valor FROM estado_sync`).
- **Qué se cambió:** `.mapToOneOrNull(io).map { fila -> fila?.valor?.let { Instant.parse(it) } }`.

### B3 · columnAdapters
- **Verificado:** el esquema `MilkFlow.sq` no usa cláusulas `... AS <tipo>`, así que SQLDelight
  **no genera ni requiere ningún `ColumnAdapter`**. `MilkFlowDatabase(driver)` es constructor de un
  solo argumento. La conversión enum/UUID/instante se hace en los mapeadores
  (`INTEGER → Long → kotlin.time.Instant`, `TEXT → String → enum`), que es la decisión de diseño
  original y sigue en pie.

### B4 · Nombres reales
- Tarea de generación: `generateCommonMainMilkFlowDatabaseInterface` (no `...MilkFlowInterface`:
  el sufijo es el nombre de la base `MilkFlowDatabase`).
- Tipos de fila: `Rutas`, `Zonas`, `Productores`, `Jornadas`, `Recolecciones`, `Recepciones`,
  `Inspecciones`, `Sanciones`, `Avisos`, `Precios`, `Liquidaciones`, `Solicitudes_ruta`, `Outbox`,
  `Sesion` — propiedades en `snake_case` verbatim. Los mapeadores ya usaban esos nombres: sin cambios.
- `lotePendiente(value_: Long)`, `contarPendientes(): Query<Long>`,
  `tieneSancionAguaVigente(): Query<Long>`, `totalLitrosJornada(): Query<Double>`,
  `recoleccionExiste(): Query<Long>`, `cursorPorAmbito(): Query<String>` — todos como se asumió.
- `db.transaction { }` (Transacter síncrono): correcto, sin cambios.

---

## C · Errores de plataforma (Kotlin/Native)

Patrón: `compileKotlinMetadata` y `compileAndroidMain` pasaban, pero
`compileKotlinIosSimulatorArm64` fallaba — porque **Kotlin/Native no aplica los imports por
defecto de la JVM**.

### C1 · `@JvmInline` sin resolver en el target Apple
- **Qué falló:** `Unresolved reference 'JvmInline'` en `Dni.kt`, `Litros.kt`, `TarifaPorLitro.kt`
  (4 usos).
- **Causa raíz:** `kotlin.jvm.*` está en los *default imports* de la JVM/metadata pero **no** de
  Kotlin/Native. `@JvmInline` existe en el stdlib común (multiplataforma), pero hay que importarlo
  explícitamente para que todos los targets lo vean.
- **Qué se cambió:** `import kotlin.jvm.JvmInline` en los 3 archivos de value objects.

### C2 · `todayIn` sin resolver
- **Qué falló:** `Unresolved reference 'todayIn'` en `RepositoriosEscrituraLocales.kt:144`.
- **Causa raíz:** se llamaba con receptor totalmente cualificado
  (`kotlin.time.Clock.System.todayIn(...)`) sin importar la **función de extensión**
  `kotlinx.datetime.todayIn`. Cualificar el receptor no importa la extensión.
- **Qué se cambió:** `import kotlin.time.Clock` + `import kotlinx.datetime.todayIn` +
  `import kotlinx.datetime.TimeZone`; llamada `Clock.System.todayIn(TimeZone.of("America/Lima"))`
  (igual que ya hacía `RepositoriosLocales.kt`).

### C3 · `kotlinx.datetime.Instant` → `kotlin.time.Instant`
- (Migración de la ronda anterior, incluida aquí por trazabilidad.) kotlinx-datetime 0.8.0 movió
  el instante al stdlib (`kotlin.time.Instant` / `kotlin.time.Clock`) y dejó `kotlinx.datetime`
  solo para `LocalDate` / `TimeZone` / extensiones. `sed` sobre `import kotlinx.datetime.Instant`
  y `import kotlinx.datetime.Clock` en ~20 archivos.

---

## D · Superficie de librería / **problema de diseño detectado**

### D1 · La API pública del módulo filtraba un tipo de Ktor
- **Qué falló:** `:androidApp:compileDebugKotlin` → `Unresolved reference 'io'` /
  `Cannot access class 'io.ktor.client.plugins.logging.LogLevel'`.
- **Causa raíz — es un problema de diseño, no un error de compilación a silenciar:**
  `ConfiguracionMilkFlow` (el DTO de arranque del paquete `di`, frontera pública del módulo)
  tenía `val nivelLogHttp: io.ktor.client.plugins.logging.LogLevel`. `shared` expone Ktor como
  `implementation` (no `api`) — correcto para Clean Architecture — así que el tipo de Ktor
  **no está en el classpath de compilación de los consumidores**. Un módulo con capas no debe
  exponer tipos de infraestructura por su API.
- **Qué se cambió:** enum propio `pe.edu.upeu.milkflow.core.NivelLogHttp { NINGUNO, BASICO, TODO }`.
  `construirClienteMilkFlow` lo traduce a `LogLevel` **internamente** (`when` → `NONE/INFO/ALL`).
  `ConfiguracionMilkFlow.nivelLogHttp: NivelLogHttp`.
- **Por qué así:** restablece la frontera del módulo; ningún consumidor necesita ya depender de Ktor.

### D2 · Imports de Ktor 3.2 — verificados por compilación
Se confirmó (compilación exitosa) que en Ktor 3.2:
- `ContentNegotiation` → `io.ktor.client.plugins.contentnegotiation.ContentNegotiation`
- `Logging`, `LogLevel`, `Logger` → `io.ktor.client.plugins.logging.*`
- `Auth`, `bearer`, `BearerTokens` → `io.ktor.client.plugins.auth.*` /
  `io.ktor.client.plugins.auth.providers.*`
- `DefaultRequest` → `io.ktor.client.plugins.DefaultRequest` (en la ronda anterior estaba mal como
  `io.ktor.client.plugins.defaultRequest.DefaultRequest`; ya corregido).
- `HttpRequestTimeoutException` → `io.ktor.client.plugins.*`;
  `ConnectTimeoutException`/`SocketTimeoutException` → `io.ktor.client.network.sockets.*`.
- `HttpClient(OkHttp) { }` / `HttpClient(Darwin) { }` con `engine { config { } }` /
  `engine { configureRequest { } }`: correcto.
- `bearer { loadTokens { BearerTokens(token, "") }; sendWithoutRequest { true } }`: correcto.
- Serialización: plugin `org.jetbrains.kotlin.plugin.serialization` aplicado en el módulo;
  `kotlinx-serialization-json` en `commonMain`. `@Serializable` + `Json { }` compilan en los 3
  targets.

---

## E · iOS: Keychain y NWPathMonitor — **el cinterop SÍ compila**

- El README anterior marcaba `AlmacenSeguroTokenIos` (Keychain vía `platform.Security`) y
  `ObservadorConectividadIos` (NWPathMonitor vía `platform.Network`) como riesgo, con la opción
  de sustituirlos por `expect` + inyección desde Swift.
- **Resultado real:** `:shared:compileKotlinIosSimulatorArm64` y
  `:shared:compileTestKotlinIosSimulatorArm64` **compilan sin errores** con la implementación
  cinterop tal cual. `CFDictionaryCreateMutable` + `CFDictionaryAddValue` + `CFBridgingRetain`,
  `SecItemAdd/CopyMatching/Delete`, y `nw_path_monitor_*` resuelven correctamente contra las
  cabeceras de la plataforma que trae Kotlin/Native.
- **No se hizo el refactor a Swift.** No hacía falta romper la frontera; se mantiene una única
  implementación KMP.
- **Pendiente honesto:** compila, pero *no se ejecutó* en un dispositivo/simulador iOS (esto es
  Windows). El comportamiento en runtime del Keychain debe probarse en Xcode; si diera problemas,
  el punto de extensión limpio sigue disponible (cambiar el binding de `AlmacenSeguroToken` en
  `moduloPlataforma()` de iOS).

---

## F · Tests

`src/commonTest/` (fakes, lógica pura y del `SyncManager`):
- `EvaluadorCalidadTest` — 8 ✅ (RN-05 4.9 %/5.0 %, RN-06 pH 6.49/6.5, combinaciones)
- `RegistrarRecoleccionUseCaseTest` — 5 ✅
- `OutboxIdempotenciaTest` — 3 ✅ (SyncManager sobre outbox en memoria)
- `ResolucionConflictosTest` — 4 ✅ (versión / conmutativo / rechazo)

`src/androidHostTest/` — **nuevo, con driver real** (JdbcSqliteDriver en memoria, `sqlite-driver`
ya declarado en `androidHostTest`), para no ocultar errores de esquema:
- `EsquemaMilkFlowTest` — 4 ✅
  - `ON CONFLICT(tabla, id_registro) DO UPDATE` del outbox: reencolar el mismo registro deja **una**
    operación con el último payload;
  - `RecoleccionRepositoryLocal.registrar` escribe negocio **y** outbox en la misma transacción;
  - una violación de `recolecciones_jornada_productor (UNIQUE)` devuelve `Resultado.Fallo` y **no**
    deja fila de negocio ni operación de outbox a medias (atomicidad real verificada);
  - `cursores_sync` persiste el cursor por ámbito.

`iosSimulatorArm64Test`: **SKIPPED** en el host Windows (Kotlin lo omite correctamente; se
ejecutaría en un runner macOS).

Total ejecutado: **24 tests, 0 fallos**. Ningún test comentado ni borrado.

---

## Notas de diseño (no parcheadas, para revisión)

1. **`JornadaRepositoryLocal` recibe `RecepcionRepository` y no lo usa.** El cierre de jornada
   inserta la recepción directamente en su propia transacción. La dependencia está de más; se dejó
   por ahora (compila y documenta la relación) pero conviene quitarla del constructor y del `single`
   de Koin.
2. **`AvisoRepositoryLocal` usa `usuarioId` como `avisos_vistos.productor_id`.** El backend deriva
   el productor del token, así que el valor local solo sirve para deduplicar el acuse por
   dispositivo. Si en el futuro se necesita el `productorId` real en el móvil, hay que traerlo en la
   respuesta de login o resolverlo tras la primera bajada.
3. **`SyncManager` arranca colectores en `init` sobre el `alcance` inyectado.** Correcto para
   producción (scope de vida del módulo), pero en tests se pasa un `CoroutineScope(Unconfined)` que
   nunca se cancela — fuga acotada e inofensiva en la suite actual. Si crece la suite, exponer un
   `cerrar()` que cancele el scope.
