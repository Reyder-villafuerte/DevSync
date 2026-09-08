package pe.edu.upeu.milkflow.di

import org.koin.core.module.Module
import org.koin.core.module.dsl.viewModel
import org.koin.dsl.module
import pe.edu.upeu.milkflow.presentation.acopiador.AcopiadorViewModel
import pe.edu.upeu.milkflow.presentation.auditoria.AuditoriaViewModel
import pe.edu.upeu.milkflow.presentation.calidad.CalidadViewModel
import pe.edu.upeu.milkflow.presentation.calidad.SupervisorConsultasCalidadViewModel
import pe.edu.upeu.milkflow.presentation.consultas.ConsultaViewModel
import pe.edu.upeu.milkflow.presentation.consultas.ResumenViewModel
import pe.edu.upeu.milkflow.presentation.entrega.EntregaViewModel
import pe.edu.upeu.milkflow.presentation.inicio.InicioViewModel
import pe.edu.upeu.milkflow.presentation.inicio.ProduccionViewModel
import pe.edu.upeu.milkflow.presentation.login.LoginViewModel
import pe.edu.upeu.milkflow.presentation.registro.RegistroViewModel
import pe.edu.upeu.milkflow.presentation.perfil.PerfilViewModel
import pe.edu.upeu.milkflow.presentation.productor.ProductorViewModel
import pe.edu.upeu.milkflow.presentation.reportes.ReporteViewModel
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario
import pe.edu.upeu.milkflow.presentation.sincronizacion.SincronizacionViewModel
import pe.edu.upeu.milkflow.presentation.usuarios.UsuariosViewModel

internal fun presentationModule(): Module = module {
    single { SesionUsuario() }
    viewModel { LoginViewModel(get(), get()) }
    viewModel { RegistroViewModel(get()) }
    viewModel { InicioViewModel(get(), get(), get(), get(), get(), get(), get()) }
    viewModel { ProductorViewModel(get(), get(), get(), get(), get(), get()) }
    viewModel { AcopiadorViewModel(get(), get(), get(), get(), get()) }
    viewModel { EntregaViewModel(get(), get(), get(), get(), get(), get(), get(), get()) }
    viewModel { CalidadViewModel(get(), get(), get(), get(), get(), get(), get()) }
    viewModel { SupervisorConsultasCalidadViewModel(get(), get()) }
    viewModel { ConsultaViewModel(get(), get()) }
    viewModel { ResumenViewModel(get(), get()) }
    viewModel { ReporteViewModel(get(), get(), get(), get()) }
    viewModel { SincronizacionViewModel(get(), get(), get(), get(), get(), get()) }
    viewModel { ProduccionViewModel(get(), get(), get(), get()) }
    viewModel { UsuariosViewModel(get(), get(), get(), get()) }
    viewModel { AuditoriaViewModel(get(), get(), get()) }
    viewModel { PerfilViewModel(get()) }
}
