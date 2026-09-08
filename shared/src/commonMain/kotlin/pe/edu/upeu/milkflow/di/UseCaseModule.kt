package pe.edu.upeu.milkflow.di

import org.koin.core.module.Module
import org.koin.dsl.module
import pe.edu.upeu.milkflow.domain.usecase.ActualizarProductor
import pe.edu.upeu.milkflow.domain.usecase.AutenticarUsuario
import pe.edu.upeu.milkflow.domain.usecase.GestionarUsuarios
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasProductor
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregas
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasRecientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEstadoSincronizacion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerLotesProduccion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteMensual
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteSemanal
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenCalidad
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenProduccion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenProductor
import pe.edu.upeu.milkflow.domain.usecase.ObtenerTotalLeche
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAcopiador
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarCuenta
import pe.edu.upeu.milkflow.domain.usecase.RegistrarEntregaDirecta
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLecheRecogida
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLoteProduccion
import pe.edu.upeu.milkflow.domain.usecase.RegistrarProblemaLeche
import pe.edu.upeu.milkflow.domain.usecase.RegistrarProductor
import pe.edu.upeu.milkflow.domain.usecase.RegistrarPruebaCalidad
import pe.edu.upeu.milkflow.domain.usecase.SincronizarRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario

internal fun useCaseModule(): Module = module {
    factory { AutenticarUsuario(get()) }
    factory { ActualizarProductor(get()) }
    factory { RegistrarAcopiador(get()) }
    factory { RegistrarProductor(get()) }
    factory { RegistrarEntregaDirecta(get(), get()) }
    factory { RegistrarLecheRecogida(get(), get(), get()) }
    factory { ObtenerTotalLeche(get()) }
    factory { RegistrarPruebaCalidad(get(), get()) }
    factory { RegistrarProblemaLeche(get(), get()) }
    factory { ObtenerEntregasProductor(get(), get()) }
    factory { ObtenerEntregas(get()) }
    factory { ObtenerEntregasRecientes(get()) }
    factory { ObtenerResumenProductor(get(), get()) }
    factory { ObtenerReporteDiario(get()) }
    factory { ObtenerReporteSemanal(get()) }
    factory { ObtenerReporteMensual(get()) }
    factory { ObtenerRegistrosPendientes(get()) }
    factory { SincronizarRegistrosPendientes(get()) }
    factory { ObtenerEstadoSincronizacion(get()) }
    factory { GestionarUsuarios(get()) }
    factory { RegistrarAuditoria(get()) }
    factory { RegistrarCuenta(get()) }
    factory { RegistrarLoteProduccion(get()) }
    factory { ObtenerLotesProduccion(get()) }
    factory { ObtenerResumenProduccion(get()) }
    factory { ObtenerResumenCalidad(get()) }
    single { ValidarPermisoUsuario() }
}
