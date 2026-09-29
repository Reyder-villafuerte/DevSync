# Flujos de dominio (`overview/workflows/`)

Guías por flujo de negocio (pasos ordenados). **No** sustituye `architecture.md` (mapa técnico) ni `context/` (datos/referencia).

Crear un archivo por flujo, ej. `flujo_procesamiento_datos.md`:

```markdown
# Flujo: [nombre genérico de arquitectura]

1. Captura / Origen
2. Procesamiento / Persistencia
3. Salida / Transformación
```

Solo documentar flujos que el trabajo actual toque; evitar mapeos exhaustivos no requeridos en el bootstrap.
