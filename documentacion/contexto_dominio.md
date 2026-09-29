# Contexto de Dominio: MilkFlow Huata (Web + Móvil)

> Tipo: Reglas de negocio y modelo operativo
> Actualizado: 2026-09-13

## 1. Roles del Sistema (8 Roles)
1. **Productor / Proveedor**: Entrega leche en su zona, consulta historial de entregas diarias, resultados Lactoscan y citas técnicas. Solicita cambio de zona.
2. **Acopiador**: Sale en camión 4:30 AM con lista asignada por zona. Registra litros por proveedor. Descarga en caudalímetro de planta. Recibe anuncios de reasignación al login.
3. **Administrador**: Gestión de usuarios, asignación de rutas/acopiadores (5 acopiadores con descansos rotativos), aprobación de cambios de zona y anuncios con rango de fechas.
4. **Jefe de Producción**: Verificación de litros en planta (conforme/incompleto con cantidad real) -> pasa a stock oficial de leche. Planifica moldes de queso (descuenta 10 L de leche por molde) y transfiere queso terminado a stock.
5. **Personal de Pago**: Liquidaciones y pagos a productores según entregas validadas.
6. **Inspector de Calidad**: Pruebas con Lactoscan (acidez, grasa, densidad, agua, etc.). Notifica al productor y genera cita de visita técnica si hay acidez elevada u anomalías.
7. **Personal de Venta / Despacho**: Venta de queso en stock. Pago solo en efectivo. Emisión de recibo formal. Detección automática de tipo de cliente:
   - Proveedor: S/ 18.00 por molde.
   - Mayorista registrado / recurrente (>10 quesos o marcado): S/ 19.00 por molde.
   - Cliente local / regular: S/ 20.00 por molde.
   - Búsqueda ágil por apellido; alta rápida de nuevo cliente en caja.
8. **Jefe General / SuperAdmin**: Visión consolidada integral, métricas de producción, ventas, finanzas y auditoría.

## 2. Zonas de Huata (Distrito de Huata)
- **Zona 1 (Zona Urbana Central)**: Núcleo principal de la ciudad / capital de Huata.
- **Zona 2 (Sectores del Sur)**: Sectores del sur: Ñuñure, Joche y Sancachi.
- **Zona 3 (Sectores Agropecuarios del Norte/Interior)**: Yasin, Juchuy Moro, Moro Viejo y Llankako.
- **Zona 4 (Sectores de Faón, Jhochi y Karata)**: Comunidades de Faón, Jhochi y Karata.

## 3. Flujo Operativo Principal
`Acopio en ruta (4:30 AM)` -> `Descarga en planta con caudalímetro` -> `Verificación Jefe Producción (litros reales en stock)` -> `Elaboración queso (1 molde = -10 L stock)` -> `Ingreso stock queso` -> `Venta y despacho con recibo`.

