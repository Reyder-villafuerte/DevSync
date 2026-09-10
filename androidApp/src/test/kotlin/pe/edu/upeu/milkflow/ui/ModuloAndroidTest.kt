package pe.edu.upeu.milkflow.ui

import org.koin.test.verify.verify
import pe.edu.upeu.milkflow.di.moduloAndroid
import pe.edu.upeu.milkflow.domain.calidad.EvaluadorCalidad
import pe.edu.upeu.milkflow.domain.repository.AvisoRepository
import pe.edu.upeu.milkflow.domain.repository.InspeccionRepository
import pe.edu.upeu.milkflow.domain.repository.JornadaRepository
import pe.edu.upeu.milkflow.domain.repository.LiquidacionRepository
import pe.edu.upeu.milkflow.domain.repository.PrecioRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.RecoleccionRepository
import pe.edu.upeu.milkflow.domain.repository.RutaRepository
import pe.edu.upeu.milkflow.domain.repository.SancionRepository
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.domain.repository.SesionRepository
import pe.edu.upeu.milkflow.domain.repository.SolicitudRutaRepository
import pe.edu.upeu.milkflow.domain.repository.ZonaRepository
import pe.edu.upeu.milkflow.domain.sync.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.usecase.CerrarJornadaUseCase
import pe.edu.upeu.milkflow.domain.usecase.EvaluarCalidadUseCase
import pe.edu.upeu.milkflow.domain.usecase.IniciarJornadaUseCase
import pe.edu.upeu.milkflow.domain.usecase.IniciarSesionUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerAvisoActivoUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEstadoSincronizacionUseCase
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteAcopioUseCase
import pe.edu.upeu.milkflow.domain.usecase.RegistrarRecoleccionUseCase
import pe.edu.upeu.milkflow.domain.usecase.SincronizarAhoraUseCase
import pe.edu.upeu.milkflow.domain.usecase.SolicitarCambioRutaUseCase
import kotlin.test.Test

/**
 * Verificación ESTÁTICA del grafo de ViewModels (sin instanciar nada, sin
 * Android). Falla en compilación de tests si un ViewModel pide por constructor
 * un tipo que ni `moduloAndroid` ni `moduloCompartido`/`moduloPlataforma`
 * proveen — que es la causa típica de un crash de Koin al abrir la app.
 */
class ModuloAndroidTest {

    @Test
    fun el_grafo_de_viewmodels_resuelve() {
        moduloAndroid.verify(
            extraTypes = listOf(
                // Parámetros de navegación (parametersOf).
                SesionActiva::class, String::class,
                // Provisto por moduloCompartido / moduloPlataforma.
                EvaluadorCalidad::class,
                SesionRepository::class, RutaRepository::class, ZonaRepository::class,
                ProductorRepository::class, JornadaRepository::class, RecoleccionRepository::class,
                InspeccionRepository::class, SancionRepository::class, AvisoRepository::class,
                PrecioRepository::class, LiquidacionRepository::class, SolicitudRutaRepository::class,
                SincronizacionRepository::class,
                ObtenerEstadoSincronizacionUseCase::class, SincronizarAhoraUseCase::class,
                ObtenerReporteAcopioUseCase::class, IniciarSesionUseCase::class,
                IniciarJornadaUseCase::class, RegistrarRecoleccionUseCase::class,
                CerrarJornadaUseCase::class, EvaluarCalidadUseCase::class,
                ObtenerAvisoActivoUseCase::class, SolicitarCambioRutaUseCase::class,
                // Lambda idDispositivo de LoginViewModel.
                Function0::class,
            ),
        )
    }
}
