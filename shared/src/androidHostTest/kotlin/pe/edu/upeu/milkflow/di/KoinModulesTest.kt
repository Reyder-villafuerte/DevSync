package pe.edu.upeu.milkflow.di

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertSame
import kotlin.test.assertTrue
import org.koin.core.context.stopKoin
import org.koin.dsl.koinApplication
import pe.edu.upeu.milkflow.data.local.DatabaseDriverFactory
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase
import pe.edu.upeu.milkflow.data.repository.SqlDelightAcopiadorRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightAuditoriaRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightCalidadRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightEntregaRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightProductorRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightSincronizacionRepository
import pe.edu.upeu.milkflow.data.repository.SqlDelightUsuarioRepository
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.repository.CalidadRepository
import pe.edu.upeu.milkflow.domain.repository.EntregaRepository
import pe.edu.upeu.milkflow.domain.repository.ProductorRepository
import pe.edu.upeu.milkflow.domain.repository.SincronizacionRepository
import pe.edu.upeu.milkflow.domain.repository.UsuarioRepository
import pe.edu.upeu.milkflow.domain.usecase.ActualizarProductor
import pe.edu.upeu.milkflow.domain.usecase.AutenticarUsuario
import pe.edu.upeu.milkflow.domain.usecase.GestionarUsuarios
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasProductor
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregas
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEntregasRecientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerEstadoSincronizacion
import pe.edu.upeu.milkflow.domain.usecase.ObtenerRegistrosPendientes
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteDiario
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteMensual
import pe.edu.upeu.milkflow.domain.usecase.ObtenerReporteSemanal
import pe.edu.upeu.milkflow.domain.usecase.ObtenerResumenProductor
import pe.edu.upeu.milkflow.domain.usecase.ObtenerTotalLeche
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAcopiador
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.RegistrarEntregaDirecta
import pe.edu.upeu.milkflow.domain.usecase.RegistrarLecheRecogida
import pe.edu.upeu.milkflow.domain.usecase.RegistrarProblemaLeche
import pe.edu.upeu.milkflow.domain.usecase.RegistrarPruebaCalidad
import pe.edu.upeu.milkflow.domain.usecase.SincronizarRegistrosPendientes
import pe.edu.upeu.milkflow.presentation.inicio.InicioViewModel
import pe.edu.upeu.milkflow.presentation.acopiador.AcopiadorViewModel
import pe.edu.upeu.milkflow.presentation.entrega.EntregaViewModel
import pe.edu.upeu.milkflow.presentation.login.LoginViewModel
import pe.edu.upeu.milkflow.presentation.productor.ProductorViewModel
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

class KoinModulesTest {
    @Test
    fun initKoinIniciaYResuelveRepositorios() {
        val application = initKoin(testPlatformModule())

        try {
            val koin = application.koin
            assertNotNull(koin.get<MilkFlowDatabase>())
            assertTrue(koin.get<UsuarioRepository>() is SqlDelightUsuarioRepository)
            assertTrue(koin.get<ProductorRepository>() is SqlDelightProductorRepository)
            assertTrue(koin.get<AcopiadorRepository>() is SqlDelightAcopiadorRepository)
            assertTrue(koin.get<EntregaRepository>() is SqlDelightEntregaRepository)
            assertTrue(koin.get<CalidadRepository>() is SqlDelightCalidadRepository)
            assertTrue(koin.get<AuditoriaRepository>() is SqlDelightAuditoriaRepository)
            assertTrue(koin.get<SincronizacionRepository>() is SqlDelightSincronizacionRepository)
        } finally {
            stopKoin()
        }
    }

    @Test
    fun resuelveTodosLosCasosDeUsoSinCiclos() {
        val application = isolatedApplication()

        try {
            val koin = application.koin
            assertNotNull(koin.get<AutenticarUsuario>())
            assertNotNull(koin.get<ActualizarProductor>())
            assertNotNull(koin.get<RegistrarAcopiador>())
            assertNotNull(koin.get<RegistrarEntregaDirecta>())
            assertNotNull(koin.get<RegistrarLecheRecogida>())
            assertNotNull(koin.get<ObtenerTotalLeche>())
            assertNotNull(koin.get<RegistrarPruebaCalidad>())
            assertNotNull(koin.get<RegistrarProblemaLeche>())
            assertNotNull(koin.get<ObtenerEntregasProductor>())
            assertNotNull(koin.get<ObtenerEntregas>())
            assertNotNull(koin.get<ObtenerEntregasRecientes>())
            assertNotNull(koin.get<ObtenerResumenProductor>())
            assertNotNull(koin.get<ObtenerReporteDiario>())
            assertNotNull(koin.get<ObtenerReporteSemanal>())
            assertNotNull(koin.get<ObtenerReporteMensual>())
            assertNotNull(koin.get<ObtenerRegistrosPendientes>())
            assertNotNull(koin.get<SincronizarRegistrosPendientes>())
            assertNotNull(koin.get<ObtenerEstadoSincronizacion>())
            assertNotNull(koin.get<GestionarUsuarios>())
            assertNotNull(koin.get<RegistrarAuditoria>())
        } finally {
            application.close()
        }
    }

    @Test
    fun resuelveSesionYViewModelsDePresentation() {
        val application = isolatedApplication()

        try {
            val koin = application.koin
            assertNotNull(koin.get<SesionUsuario>())
            assertNotNull(koin.get<LoginViewModel>())
            assertNotNull(koin.get<InicioViewModel>())
            assertNotNull(koin.get<ProductorViewModel>())
            assertNotNull(koin.get<AcopiadorViewModel>())
            assertNotNull(koin.get<EntregaViewModel>())
            assertEquals(1, koin.getAll<SesionUsuario>().size)
        } finally {
            application.close()
        }
    }

    @Test
    fun repositoriosYBaseDeDatosNoSeDuplican() {
        val application = isolatedApplication()

        try {
            val koin = application.koin
            assertSame(koin.get<ProductorRepository>(), koin.get<ProductorRepository>())
            assertSame(koin.get<EntregaRepository>(), koin.get<EntregaRepository>())
            assertSame(koin.get<MilkFlowDatabase>(), koin.get<MilkFlowDatabase>())
            assertEquals(1, koin.getAll<ProductorRepository>().size)
            assertEquals(1, koin.getAll<EntregaRepository>().size)
            assertEquals(1, koin.getAll<MilkFlowDatabase>().size)
        } finally {
            application.close()
        }
    }

    private fun isolatedApplication() = koinApplication {
        allowOverride(false)
        modules(milkFlowModule(testPlatformModule()))
    }

    private fun testPlatformModule() = platformModule(
        object : DatabaseDriverFactory {
            override fun createDriver(): SqlDriver =
                JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { driver ->
                    MilkFlowDatabase.Schema.create(driver)
                }
        },
    )
}
