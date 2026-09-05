# MilkFlow

Sistema de gestión de recolección de leche con soporte offline, arquitectura limpia y multiplatforma.

## Tecnologías
- **Kotlin Multiplatform**
- **Compose Multiplatform**
- **Clean Architecture**
- **MVVM**
- **Koin** (Inyección de dependencias)
- **Coroutines & Flow**
- **SQLDelight** (Persistencia local)
- **Material 3**

## Arquitectura
El proyecto sigue los principios de Clean Architecture:
- **Presentation**: Composables, ViewModels y StateFlow para la UI.
- **Domain**: Modelos de negocio, Repositorios (interfaces) y Casos de Uso.
- **Data**: Implementaciones de repositorios, mapeadores y base de datos local.

## Funcionalidades
- Gestión de Productores y Acopiadores.
- Registro de Entregas (Directa y Recogida).
- Control de Calidad y Registro de Problemas.
- Consultas y Reportes (Diario, Semanal, Mensual).
- Sincronización Offline con manejo de estados (PENDIENTE, ENVIADO, ERROR).
- Gestión de Usuarios y Permisos por Rol.
- Auditoría de operaciones críticas.

## Estado de Requisitos (RF)
- **RF-01: Autenticar usuario**: CUMPLIDO (Nota: Inconsistencia documentada en trazabilidad).
- **RF-02: Gestionar productores**: CUMPLIDO.
- **RF-03: Gestionar acopiadores**: CUMPLIDO.
- **RF-04: Registrar entrega directa**: CUMPLIDO.
- **RF-05: Registrar leche recogida**: CUMPLIDO.
- **RF-06: Registrar prueba calidad**: CUMPLIDO.
- **RF-07: Registrar problema leche**: CUMPLIDO.
- **RF-08: Consultar entregas**: CUMPLIDO.
- **RF-09: Resumen productor**: CUMPLIDO.
- **RF-10: Avisar al productor**: PARCIAL (Sin infraestructura real de notificaciones).
- **RF-11: Reporte diario/semanal/mensual**: CUMPLIDO.
- **RF-12: Totales leche**: CUMPLIDO.
- **RF-13: Guardar sin Internet**: CUMPLIDO.
- **RF-14: Enviar datos al recuperar Internet**: CUMPLIDO.
- **RF-15: Estados de sincronización**: CUMPLIDO.
- **RF-16: Usuarios y Permisos**: CUMPLIDO.

## Estado de Reglas de Negocio (RN)
- **RN-01 a RN-10**: CUMPLIDO.

## Estado de Requisitos No Funcionales (RNF)
- **RNF-01 a RNF-04**: CUMPLIDO.

## Funcionamiento Offline
La aplicación permite realizar todas las operaciones sin conexión a Internet. Los registros se guardan localmente con el estado `PENDIENTE`. El usuario puede sincronizarlos manualmente desde la pantalla de Sincronización. El sistema evita duplicados verificando el estado antes de enviar.

## Limitaciones
- **Backend**: No existe un backend real; la sincronización simula el envío y falla por defecto (configurable en `SqlDelightSincronizacionRepository`).
- **Notificaciones**: RF-10 no tiene implementación técnica de envío de mensajes externos.

## Ejecución
### Android
Ejecutar la tarea Gradle `:androidApp:assembleDebug` y desplegar en un dispositivo o emulador.
### Pruebas
Ejecutar `./gradlew :shared:testAndroidHostTest` para las pruebas unitarias de lógica y ViewModels.
