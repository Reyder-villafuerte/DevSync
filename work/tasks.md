# Tarea Activa (`overview/work/tasks.md`)

> Espacio de trabajo activo para la tarea en ejecución. Escribir aquí antes de modificar código.

## 🎯 Tarea Actual

- **ID:** `[w31]`
- **Estado:** ✅ Completado (suite 90/90 verde, verificado en navegador)
- **Descripción:** La categoría solo necesita **nombre, descripción y estado activa/inactiva**. Se quitan el ámbito («Sirve para») y los conteos de insumos/productos de la tabla.

## 🏷️ Clasificación

- [ ] **Problema (Bug):** Comportamiento inesperado o fallo funcional.
- [x] **Mejora (Feature):** Simplificación del modelo de categoría.
- [x] **Deuda / Refactor:** Se elimina la columna `scope` y su lógica derivada.

## 🛤️ Resultado

- Migración `2026_09_16_210000_drop_scope_from_inventory_categories`: fuera la columna `scope`. Una categoría ya no se divide entre insumo y producto; agrupa cualquier cosa del almacén.
- Tabla de categorías: **Categoría · Descripción · Estado · Acciones**. El filtro pasa de ámbito a **Todas / Activas / Inactivas**.
- Modal: nombre, descripción y un interruptor **Categoría activa** que solo aparece al editar (una categoría nueva nace activa).
- Los selects de alta de insumos (`/produccion/almacen`) y de productos (`/produccion/productos`) ofrecen ahora **todas las categorías activas**; una categoría desactivada desaparece de ambos sin borrar nada.
- `CatalogoService::crearCategoria()` y `actualizarCategoria()` pierden el parámetro de ámbito; `actualizarCategoria()` gana el de estado.

### Nota

Las columnas de conteo se fueron de la vista, pero la regla sigue viva: al intentar eliminar una categoría con ítems dentro, el error dice cuántos insumos y productos la ocupan.
