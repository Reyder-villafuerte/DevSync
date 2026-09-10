# MilkFlow — Backend (Laravel 11)

Backend centralizado de la plataforma de gestión lechera de la asociación de
productores de Puno. Autenticación con **Sanctum**, persistencia en
**PostgreSQL** vía Eloquent, panel de escritorio con **Blade + Livewire** y
**API de sincronización offline-first** para la capa `shared` de Kotlin
Multiplatform (SQLDelight + Ktor).

## Arquitectura (equivalencia de capas)

| Capa (Clean / KMP)      | Aquí                                             |
|-------------------------|--------------------------------------------------|
| Domain — Entities       | `app/Models`                                     |
| Domain — Use Cases      | `app/Services`                                   |
| Data — Repository/ACID  | Eloquent + `DB::transaction` en los Services     |
| Presentation — UI       | `app/Http/Controllers` + `resources/views` (Livewire) |

Los **controladores no contienen lógica de negocio**: validan (Form Requests) y
delegan en `app/Services`. Toda escritura que toca stock o liquidaciones va en
transacción.

## Puesta en marcha

```bash
composer install
cp .env.example .env
php artisan key:generate

# PostgreSQL (requiere permiso para CREATE EXTENSION btree_gist)
createdb milkflow
php artisan migrate --seed

php artisan serve
```

Usuarios sembrados (contraseña `milkflow2026`), uno por rol:

| Rol                  | DNI      |
|----------------------|----------|
| Acopiador            | 70000001 |
| Supervisor de Calidad| 70000002 |
| Productor            | 70000003 |
| Jefe de Producción   | 70000004 |
| Despacho y Ventas    | 70000005 |
| Administración       | 70000006 |

Panel web: `http://localhost:8000/login` (solo Jefe de Producción, Despacho y
Administración). App móvil: `POST /api/login` con `{ dni, password, dispositivo }`.

## Reglas de negocio implementadas (`app/Services`)

| Regla | Service | Nota |
|-------|---------|------|
| RN-05 adulteración con agua | `Calidad\EvaluacionCalidadService` | <5% 1ra→advertencia+descuento; <5% reincidente→descuento+retiro; ≥5%→expulsión+tarifa mínima. Dictamen **persistido**. |
| RN-06 acidez | `Calidad\EvaluacionCalidadService` | pH<6.5 → rechazo de lote + capacitación obligatoria, sin expulsión. |
| RN-08 rendimiento | `Produccion\RendimientoService` | Meta 11–12 quesos / 100 L; se calcula y persiste al completar la sesión. |
| Tolerancia volumétrica | `Acopio\ConciliacionService` | Diferencia acopiador vs. caudalímetro >1% → alerta persistida. |
| Liquidación semanal | `Liquidacion\LiquidacionService` | Ciclo jue→mié; congela tarifa y sanciones; aplica descuentos. |
| Movimiento de stock | `Stock\StockService` + `Produccion\SesionProduccionService` | Libro de eventos + vista materializada `stock_actual`. |
| Correlativo por dispositivo | `Facturacion\CorrelativoService` | Reserva de rangos sin huecos ni repeticiones; `liberarRangosDelDia()` al cierre. |
| Sincronización | `Sync\SyncService` + `Sync\ResolutorAmbito` | Bajada por cursor de timestamp filtrada por ámbito de rol; subida idempotente por UUID con reglas de conflicto por dominio. |
| Quórum de asamblea | `Asamblea\AsambleaService` | Padrón y quórum congelados al abrir registro. |

## API móvil (Ktor Client)

JSON en **camelCase**, BD en snake_case (traducción en `SyncService`). El
dispositivo se resuelve del token (`disp:<uuid>`), no se acepta del body.
Rutas agrupadas por `rol:` en `routes/api.php`; autorización fina con policies.

```
POST /api/login                 { dni, password, dispositivo:{identificador,nombre,plataforma} }
                                 -> { token, dispositivoId, usuario:{...,rol}, ambito:"ruta:01" }
POST /api/logout                 revoca el token actual

GET  /api/sync/pull?desde={iso}&ambito={ruta:01|zona:norte|productor:<uuid>|global}
     -> { servidorEn, cursor, hayMas, ambito, cambios:{ productores:[...], preciosCompraLeche:[...], ... } }
     · solo filas con updated_at > cursor (incluye deleted=true)
     · filtrado por ámbito del rol: un acopiador recibe SU padrón y la tarifa vigente, no las ventas de planta

POST /api/sync/push              { operaciones:[ {entidad, id, versionBase, atributos, eliminar} ] }
     -> 200 | 207  { aceptadas:[{id,entidad,version,resultado}], conflictos:[{id,entidad,motivo,servidor}], rechazadas:[...], resumen }
     · idempotente por UUID (reenviar el mismo lote no duplica)
     · una transacción por tabla + savepoint por operación
     · conflicto por dominio: recolecciones/inspecciones = solo inserción;
       movimientos de stock = conmutativos; precios/avisos/liquidaciones = gana el servidor;
       cabecera de jornada = concurrencia optimista por `version`

POST /api/jornadas/{id}/cerrar   { registros:[...], descarga:{...}, controlesCalidad:[...] }   (rol acopiador)
POST /api/inspecciones           { id?, productorId, aguaAnadidaPorcentaje?, ph?, ... }         (rol supervisor)
     -> { control:{...,dictamen}, sancion:{...}|null, capacitacion:{...}|null }
GET  /api/avisos/activos         avisos publicados y no expirados, para el pop-up  (rol productor)
POST /api/avisos/{id}/visto      acuse del pop-up obligatorio
POST /api/correlativos/reservar  { tipoComprobante, serie, tamano? }               (rol despacho)
POST /api/correlativos/cerrar-dia   libera los rangos del día del dispositivo
```

Catálogo de entidades, dirección, estrategia de conflicto y visibilidad por
rol en `config/sync.php`. Parámetros de negocio en `config/milkflow.php`.

## Documentos de diseño

- `docs/ERD.md` — diagrama entidad-relación (Mermaid).
- `docs/DECISIONES-DISENO.md` — alternativas descartadas y por qué.

## Pruebas

```bash
php artisan test
```

- `tests/Unit/RendimientoServiceTest.php` — RN-08.
- `tests/Feature/EvaluacionCalidadServiceTest.php` — RN-05 / RN-06 + no-recálculo del dictamen.
- `tests/Feature/CorrelativoServiceTest.php` — rangos sin huecos ni repeticiones.
- `tests/Feature/StockVentaTest.php` — venta rechazada con `stock_insuficiente`.
- `tests/Feature/Api/SyncPullTest.php` — pull incremental, filtro por ámbito, borrados.
- `tests/Feature/Api/SyncPushTest.php` — idempotencia, conflicto de versión, conmutatividad de stock.
- `tests/Feature/Api/InspeccionTest.php` — `POST /api/inspecciones` devuelve el dictamen evaluado.
