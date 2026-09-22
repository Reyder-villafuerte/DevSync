# Sesión actual

- Fecha: 2026-09-16
- Agente: Claude Opus 5
- Nodo activo: p4
- Estado validación: exitoso (suite `MilkFlowWeb` 90/90 con 558 aserciones, `vendor/bin/pint --dirty` aplicado, páginas verificadas en navegador)

## Cambios realizados

- Tarea `w25` (Tarea): credencial única por DNI en web y móvil + juego de datos semilla escrito en código.

### `MilkFlowWeb`

- `app/Http/Controllers/AuthController.php`: el login web autentica por `dni` (acepta correo como respaldo cuando el valor trae `@`) y corta la sesión con mensaje explícito si la cuenta está inactiva.
- `resources/views/auth/login.blade.php`: campo **DNI** en lugar de correo y ayuda de acceso rápido con los DNI semilla por rol.
- Migración `2026_09_16_000000_add_unique_index_to_users_dni`: libera los DNI repetidos (conserva al usuario más antiguo) y aplica `unique('dni')`. Cierra la deuda `d1`.
- `database/seeders/UsuariosRolesSeeder.php`: un acceso por rol — 7 de planilla (`70000001`–`70000007`) + 5 acopiadores (`71110001`–`71110005`). Contraseña `password` para todos.
- `database/seeders/ProveedoresSeeder.php`: 52 proveedores, 13 por zona, DNI `400{zona}{correlativo}` (`40010001`–`40040013`), cada uno con su `Customer` tipo `proveedor` vinculado.
- `database/seeders/MilkFlowHuataSeeder.php`: crea las zonas, encadena los dos seeders nuevos y conserva los datos de demostración. Ya no define usuarios inline.
- `tests/Feature/CredencialesDniTest.php`: 7 pruebas nuevas (login web por DNI, mismo DNI en `/api/sync/login`, credencial inválida, cuenta inactiva, los 9 roles sembrados, 52 proveedores 13×zona, unicidad del DNI).
- Bug `b1` resuelto de paso: los datos de demostración cerraban la jornada de hoy y la regla nueva de `JornadaOperativa` rechazaba una segunda entrega, dejando 3 pruebas en rojo antes de esta sesión. La semana activa del seeder termina ayer y la jornada de hoy queda abierta.

### `MilkFlowMovil`

- Sin cambios: `/api/sync/login` y la pantalla de login ya aceptaban DNI o correo. La credencial es ahora literalmente la misma en ambos frentes.

### Decisión del usuario

- **No se crea un rol nuevo de pago.** `personal_pago` (liquida y autoriza en oficina) y `pagador_campo` (entrega sobres en ruta) ya cubren el pago al proveedor; lo que faltaba era el usuario semilla con DNI, ya creado.

- Tarea `w26` (Tarea): catálogo de producción abierto — la planta deja de saber solo hacer queso.

### `MilkFlowWeb` (módulo nuevo)

- Migración `2026_09_16_190840_create_production_catalog_tables`: `inventory_categories`, `supplies`, `products`, `product_recipe_items`, `production_orders`, `production_order_items` + `inventory_category_id` en `inventory_stocks`.
- `app/Services/Produccion/CatalogoService.php`: crear categorías (las inventa el usuario), insumos con unidad y stock, productos con receta y tres precios, y ajustes manuales de almacén.
- `app/Services/Produccion/ProduccionService.php`: `planificar` → `iniciar` (descuenta insumos) → `terminar` (solo cuando vencen las horas de proceso) → `cancelar` (devuelve insumos).
- Páginas: `/produccion/almacen` (categorías + insumos + stock), `/produccion/productos` (receta y precios, con filas dinámicas en JS) y `/produccion/lotes` (ciclo de vida del lote). Enlaces de sidebar para `jefe_produccion`, `admin` y `jefe_general`.
- `CatalogoProduccionSeeder`: categorías base, 6 insumos (leche, cuajo, sal, cultivo de yogurt, pulpa de fresa, botella 1 L) y el queso como primer producto con receta de 10 L y precios 18/19/20.
- `tests/Feature/CatalogoProduccionTest.php`: 12 pruebas nuevas.

- Tarea `w27` (Tarea): el esquema que propuso el usuario, cerrado sobre el catálogo de `w26`.

- `measurement_units`: la unidad de medida deja de ser texto libre y pasa a catálogo configurable (L, ml, g, kg, und, mold), enlazada desde `supplies` y `products`.
- `supply_movements` + `AlmacenService`: kardex de insumos. Todo movimiento de stock pasa por un solo punto y deja motivo (`ingreso`, `consumo_produccion`, `devolucion`, `ajuste`), saldo resultante, lote y responsable.
- Web: selector de unidad, alta de unidades nuevas y tabla de kardex en `/produccion/almacen`; el ajuste manual pide motivo.
- 3 pruebas nuevas en `CatalogoProduccionTest`.
- Base de desarrollo al día: migraciones aplicadas y `CatalogoProduccionSeeder` (idempotente) ejecutado.

- Tarea `w28` (Tarea): semilla del catálogo garantizada por prueba.

- `tests/Feature/SemillaCatalogoTest.php`: siembra por `DatabaseSeeder` (lo mismo que corre `migrate:fresh --seed`) y afirma unidades, categorías, insumos con stock, productos con receta y precios, y que re-sembrar no duplica.
- Segundo producto de ejemplo `YOGURT_FRESA_1L` con receta de cuatro insumos de distintas categorías.

- Tarea `w29` (Mejora): «Almacén y Categorías» partido en dos ítems de menú — «Categorías y Unidades» (`InventoryCategoryController`) y «Almacén e Insumos» (`CatalogoAlmacenController`).

- Tarea `w30` (Mejora): `/produccion/categorias` pasa a ABM — botones con modal (alta y edición en el mismo formulario), tablas con Editar/Eliminar, buscador y filtro. Reglas: no se borra categoría con ítems ni unidad en uso; renombrar unidad propaga su abreviatura.

- Tarea `w31` (Mejora): la categoría se queda con nombre, descripción y activa/inactiva. Se eliminó la columna `scope`; los selects de alta ofrecen todas las categorías activas.

## Reanudar

- Siguiente nodo/tarea: esperando instrucciones del usuario.
- Agente que reanuda: cualquiera.
- Contexto crítico:
  - Credencial estándar: **DNI + `password`**, idéntica en web y móvil. Los DNI viven en código: `UsuariosRolesSeeder::PERSONAL`, `::ACOPIADORES` y `ProveedoresSeeder::POR_ZONA`.
  - `php artisan migrate:fresh --seed` reconstruye siempre el mismo juego: 4 zonas, 12 usuarios de planilla/acopio, 52 proveedores, 6 unidades de medida, 5 categorías, 6 insumos y 2 productos con receta.
  - `migrate:fresh` borra la base entera: solo sobrevive lo escrito en los seeders. Lo que se crea a mano desde la web se pierde.
  - `users.dni` es **único**. Al dar de alta un usuario nuevo, el DNI no puede repetirse o el insert falla.
  - Verificación pendiente: `migrate:fresh --seed` no se ejecutó contra la base de desarrollo (habría borrado los datos actuales del usuario); las pruebas sí corren migraciones + seeder completos sobre una base limpia.
  - El rendimiento de un producto ya NO es una constante de PHP: vive en `product_recipe_items`. `PlantaService::LITROS_POR_MOLDE` sigue existiendo solo para la pantalla vieja de Quesería, que convive con el catálogo.
  - Mapeo del esquema propuesto por el usuario: `unidad_medida`→`measurement_units`, `categoria_insumo`→`inventory_categories`, `insumo`→`supplies` (saldo en `inventory_stocks`), `producto`→`products`, `receta_insumo`→`product_recipe_items`, `movimiento_insumo`→`supply_movements`. Nombres en inglés por coherencia con el resto del esquema.
  - Las ventas siguen atadas a `sales.cheese_molds_quantity` (pendiente `p9`) y el móvil todavía no ve el catálogo (pendiente `p10`).
  - El árbol de trabajo sigue con ~360 rutas sin commitear, incluida obra en curso de otra sesión (`JornadaOperativa`, `AcopioService`, `SyncService`).
