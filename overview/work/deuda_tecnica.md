# Deuda Técnica (`overview/work/deuda_tecnica.md`)

> Errores, refactors pendientes o problemas no resueltos a nivel de código, ordenados por prioridad de impacto.

## 🔴 Prioridad Alta (Impacto Crítico / Bloqueante)

| ID | Ubicación / Componente | Descripción de la Deuda | Impacto |
|---|---|---|---|
| — | — | Sin deuda alta abierta. | — |

## 🟡 Prioridad Media (Impacto Moderado / Mantenibilidad)

| ID | Ubicación / Componente | Descripción de la Deuda | Impacto |
|---|---|---|---|
| d2 | `MilkFlowMovil/.../datos/Repositorio.kt` (814L) | Excede 3× el límite de 250L. Concentra acceso local de las 18 entidades en un solo archivo. | Lectura y edición costosas; conflictos de merge. |
| d3 | `MilkFlowMovil/.../ui/pantallas/` — `Productor.kt` (522L), `Ventas.kt` (463L), `Acopiador.kt` (438L), `Pagos.kt` (417L), `Admin.kt` (416L), `Calidad.kt` (375L), `Planta.kt` (309L) | 7 pantallas sobre el límite de 250L (máx 300L). | Composables monolíticos; difícil reutilizar sub-secciones. |
| d4 | `MilkFlowMovil/.../datos/Sincronizador.kt` (378L), `dominio/Modelos.kt` (331L), `App.kt` (310L) | Sobre el límite de 250L. | Mantenibilidad de la capa de sync y del grafo de navegación. |

## 🟢 Prioridad Baja (Mejora Menor / Estilo)

| ID | Ubicación / Componente | Descripción de la Deuda | Impacto |
|---|---|---|---|
| d5 | Árbol de trabajo git (rama `proyecto`) | 361 rutas con cambios sin commitear (15 sin seguimiento), incluidos `.idea/workspace.xml` y `.gradle/config.properties` en el índice. | Riesgo de pérdida de trabajo; ruido de IDE versionado. |

---

## ✅ Completados (Historial)

<!-- Mover aquí deuda técnica completada conservando su ID -->

| ID | Ubicación / Componente | Descripción de la Deuda | Solución Aplicada | Agente | Fecha |
|---|---|---|---|---|---|
| — | — | — | — | — | — |
