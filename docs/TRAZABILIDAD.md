# Matriz de Trazabilidad - MilkFlow

| Requisito | Implementación | Evidencia (Archivo/Clase) | Estado |
| :--- | :--- | :--- | :--- |
| **RF-01** | Autenticar usuario | `AutenticarUsuario.kt`, `LoginViewModel.kt` | CUMPLIDO |
| **RF-02** | Gestionar productores | `ProductorRepository.kt`, `ProductorViewModel.kt` | CUMPLIDO |
| **RF-03** | Gestionar acopiadores | `AcopiadorRepository.kt`, `AcopiadorViewModel.kt` | CUMPLIDO |
| **RF-04** | Registrar entrega directa | `RegistrarEntregaDirecta.kt`, `EntregaViewModel.kt` | CUMPLIDO |
| **RF-05** | Registrar leche recogida | `RegistrarLecheRecogida.kt`, `EntregaViewModel.kt` | CUMPLIDO |
| **RF-06** | Registrar prueba calidad | `RegistrarPruebaCalidad.kt`, `CalidadViewModel.kt` | CUMPLIDO |
| **RF-07** | Registrar problema leche | `RegistrarProblemaLeche.kt`, `CalidadViewModel.kt` | CUMPLIDO |
| **RF-08** | Consultar entregas | `ObtenerEntregas.kt`, `ConsultaViewModel.kt` | CUMPLIDO |
| **RF-09** | Resumen productor | `ObtenerResumenProductor.kt`, `ResumenViewModel.kt` | CUMPLIDO |
| **RF-10** | Avisar al productor | - | PARCIAL |
| **RF-11** | Reportes | `ObtenerReporteDiario.kt`, `ReporteViewModel.kt` | CUMPLIDO |
| **RF-12** | Totales leche | `ObtenerTotalLeche.kt`, `InicioViewModel.kt` | CUMPLIDO |
| **RF-13** | Guardar sin Internet | `SqlDelightSincronizacionRepository.kt` | CUMPLIDO |
| **RF-14** | Enviar datos | `SincronizarRegistrosPendientes.kt`, `SincronizacionViewModel.kt` | CUMPLIDO |
| **RF-15** | Estados sincronización | `EstadoSincronizacion.kt`, `SincronizacionScreen.kt` | CUMPLIDO |
| **RF-16** | Usuarios y Permisos | `ValidarPermisoUsuario.kt`, `UsuariosViewModel.kt` | CUMPLIDO |

## Notas Técnicas
- **RF-01**: Se mantiene como autenticación a pesar de la inconsistencia en la descripción original que mencionaba "registrar productor".
- **RF-10**: No implementado por falta de infraestructura de red externa (SMS/WhatsApp).
- **Sincronización**: Implementada mediante un repositorio que centraliza el estado de todos los registros sincronizables (Entregas, Pruebas, etc.).
- **Auditoría**: Integrada en todos los ViewModels que realizan cambios en el sistema, persistiendo en la tabla `registro_auditoria`.
- **Arquitectura**: Se verificó la separación de capas; el dominio no tiene dependencias de datos ni presentación.
