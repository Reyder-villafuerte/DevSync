# Pendientes (`overview/work/pendientes.md`)

> Elementos identificados durante el cierre de sesión (`$close`) o ejecuciones para dar seguimiento en sesiones futuras.

## 📌 Lista de Pendientes

| ID | Fecha detección | Origen / Contexto | Descripción | Estado |
|---|---|---|---|---|
| p3 | 2026-09-14 | Tarea `w22` (Admin Permisos) | Auditoría de exportación/descarga de reportes Lactoscan en PDF/Excel para supervisión de Admin. | pendiente |
| p4 | 2026-09-14 | Tarea `w22` (Planta / Calidad) | Validación de middleware por rol en rutas POST de `/planta` y `/produccion` para blindaje contra accesos no autorizados. | pendiente |
| p5 | 2026-09-14 | Tarea `w23` (Finanzas) | Exportación en PDF o comprobante imprimible de la hoja mensual de Flujo de Caja y Balance. | pendiente |
| p6 | 2026-09-14 | Tarea `w23` (Personal) | Automatización del cálculo de remuneración semanal fija o por litro para los 5 acopiadores. | pendiente |
| p7 | 2026-09-16 | Tarea `w25` (Credenciales) | Publicar tabla de credenciales semilla (DNI por rol) en `overview/context/` para el equipo. | pendiente |
| p8 | 2026-09-16 | Tarea `w25` (Móvil) | Mensaje de error explícito en login móvil cuando el DNI no existe o el usuario está inactivo. | pendiente |
| p9 | 2026-09-16 | Tarea `w26` (Catálogo) | Migrar Ventas para vender cualquier producto del catálogo, no solo `cheese_molds_quantity`; hoy la caja sigue atada al queso. | pendiente |
| p10 | 2026-09-16 | Tarea `w26` (Móvil) | Exponer catálogo y lotes al móvil: entidades en `config/sync.php` y comandos de producción en `SyncService`. | pendiente |
| p11 | 2026-09-16 | Tarea `w27` (Kardex) | Registrar también los movimientos de productos terminados, no solo de insumos. | pendiente |
| p12 | 2026-09-16 | Tarea `w27` (Kardex) | Filtro por insumo y rango de fechas + exportación del kardex. | pendiente |

- **Estados:** `pendiente`, `en progreso`, `promovido_a_task`, `descartado`, `hecho`.

---

## ✅ Completados (Historial)

| ID | Fecha Resolución | Origen / Contexto | Descripción / Solución | Agente |
|---|---|---|---|---|
| p1 | 2026-09-13 | Requerimiento `$work` | Implementados endpoints API JSON autenticados con Laravel Sanctum en `routes/api.php` para sincronización móvil offline de rutas, acopio y stock. | Gemini 3.8 Flash |
| p2 | 2026-09-13 | Requerimiento `$work` | Generación de recibo formal de venta con formato de impresión física / ticket térmico en `resources/views/ventas/receipt.blade.php`. | Gemini 3.8 Flash |
