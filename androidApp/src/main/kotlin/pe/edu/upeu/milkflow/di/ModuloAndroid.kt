package pe.edu.upeu.milkflow.di

import org.koin.android.ext.koin.androidContext
import org.koin.androidx.viewmodel.dsl.viewModel
import org.koin.core.module.dsl.viewModelOf
import org.koin.dsl.module
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.SyncViewModel
import pe.edu.upeu.milkflow.ui.navigation.SesionGateViewModel
import pe.edu.upeu.milkflow.ui.screens.acopiador.AcopiadorViewModel
import pe.edu.upeu.milkflow.ui.screens.acopiador.CierreRutaViewModel
import pe.edu.upeu.milkflow.ui.screens.acopiador.ComprobanteViewModel
import pe.edu.upeu.milkflow.ui.screens.acopiador.ReportesViewModel
import pe.edu.upeu.milkflow.ui.screens.conflictos.ConflictosViewModel
import pe.edu.upeu.milkflow.ui.screens.login.LoginViewModel
import pe.edu.upeu.milkflow.ui.screens.productor.ProductorViewModel
import pe.edu.upeu.milkflow.ui.screens.supervisor.HistorialViewModel
import pe.edu.upeu.milkflow.ui.screens.supervisor.InspeccionViewModel

/**
 * Capa de presentación: sólo ViewModels. Consumen los use cases (`factory`) y
 * repositorios (`single`) que ya expone `moduloCompartido`. Ningún ViewModel
 * toca un repositorio desde un Composable: la UI recibe `StateFlow`.
 */
val moduloAndroid = module {

    viewModelOf(::SyncViewModel)
    viewModelOf(::SesionGateViewModel)
    viewModel { ReportesViewModel(get(), get(), get()) }
    viewModelOf(::HistorialViewModel)
    viewModelOf(::ConflictosViewModel)

    viewModel { LoginViewModel(get()) { pe.edu.upeu.milkflow.ui.util.idDispositivo(androidContext()) } }

    // ViewModels que necesitan la sesión activa como parámetro de navegación.
    viewModel { (sesion: SesionActiva) ->
        AcopiadorViewModel(sesion, get(), get(), get(), get(), get(), get(), get())
    }
    viewModel { (sesion: SesionActiva) ->
        CierreRutaViewModel(sesion, get(), get(), get(), get(), get(), get())
    }
    viewModel { (sesion: SesionActiva) ->
        InspeccionViewModel(sesion, get(), get(), get(), get(), get(), get())
    }
    viewModel { (sesion: SesionActiva) ->
        ProductorViewModel(
            sesion, get(), get(), get(), get(), get(), get(), get(), get(), get(), get(), get(), get(), get(), get(),
        )
    }

    // Comprobante: recibe el id de la jornada ya cerrada.
    viewModel { (jornadaId: String) -> ComprobanteViewModel(jornadaId, get(), get(), get()) }
}
