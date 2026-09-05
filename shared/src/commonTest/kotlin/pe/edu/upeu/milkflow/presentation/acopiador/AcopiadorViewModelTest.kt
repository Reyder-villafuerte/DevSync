package pe.edu.upeu.milkflow.presentation.acopiador

import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import pe.edu.upeu.milkflow.domain.model.Acopiador
import pe.edu.upeu.milkflow.domain.model.RegistroAuditoria
import pe.edu.upeu.milkflow.domain.model.RolUsuario
import pe.edu.upeu.milkflow.domain.model.Usuario
import pe.edu.upeu.milkflow.domain.repository.AcopiadorRepository
import pe.edu.upeu.milkflow.domain.repository.AuditoriaRepository
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAcopiador
import pe.edu.upeu.milkflow.domain.usecase.RegistrarAuditoria
import pe.edu.upeu.milkflow.domain.usecase.ValidarPermisoUsuario
import pe.edu.upeu.milkflow.presentation.session.SesionUsuario

@OptIn(ExperimentalCoroutinesApi::class)
class AcopiadorViewModelTest {
    private val dispatcher = StandardTestDispatcher()

    @BeforeTest
    fun setUp() {
        Dispatchers.setMain(dispatcher)
    }

    @AfterTest
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun cargarAcopiadores() = runTest(dispatcher) {
        val viewModel = createViewModel(
            FakeAcopiadorPresentationRepository(
                Acopiador("a-2", "Zoila"),
                Acopiador("a-1", "Carlos"),
            ),
        )

        advanceUntilIdle()

        val content = assertIs<AcopiadorContentState.Success>(viewModel.uiState.value.content)
        assertEquals(listOf("Carlos", "Zoila"), content.acopiadores.map { it.nombre })
    }

    @Test
    fun registrarAcopiadorValido() = runTest(dispatcher) {
        val repository = FakeAcopiadorPresentationRepository()
        val viewModel = createViewModel(repository)
        advanceUntilIdle()
        viewModel.onEvent(AcopiadorUiEvent.NombreChanged("Luis Ramos"))

        viewModel.onEvent(AcopiadorUiEvent.Register)
        advanceUntilIdle()

        assertEquals(Acopiador("a-nuevo", "Luis Ramos"), repository.obtenerPorId("a-nuevo"))
        assertIs<AcopiadorRegistrationState.Success>(viewModel.uiState.value.registration)
        assertEquals("", viewModel.uiState.value.nombre)
    }

    @Test
    fun manejarErrorDeRegistro() = runTest(dispatcher) {
        val repository = FakeAcopiadorPresentationRepository(fallarAlGuardar = true)
        val viewModel = createViewModel(repository)
        advanceUntilIdle()
        viewModel.onEvent(AcopiadorUiEvent.NombreChanged("Luis Ramos"))

        viewModel.onEvent(AcopiadorUiEvent.Register)
        advanceUntilIdle()

        assertIs<AcopiadorRegistrationState.Error>(viewModel.uiState.value.registration)
    }

    private fun createViewModel(repository: FakeAcopiadorPresentationRepository): AcopiadorViewModel {
        val auditoria = FakeAuditoriaRepository()
        val session = SesionUsuario().apply {
            iniciar(
                Usuario(
                    id = "u-1",
                    nombreUsuario = "admin",
                    nombre = "Admin",
                    rol = RolUsuario.ADMINISTRADORA,
                    activo = true,
                ),
            )
        }
        return AcopiadorViewModel(
            acopiadorRepository = repository,
            registrarAcopiador = RegistrarAcopiador(repository),
            sesionUsuario = session,
            validarPermisoUsuario = ValidarPermisoUsuario(),
            registrarAuditoria = RegistrarAuditoria(auditoria),
            idGenerator = { "a-nuevo" },
        )
    }
}

private class FakeAcopiadorPresentationRepository(
    vararg acopiadores: Acopiador,
    private val fallarAlGuardar: Boolean = false,
) : AcopiadorRepository {
    private val datos = acopiadores.associateBy(Acopiador::id).toMutableMap()

    override suspend fun obtenerPorId(id: String): Acopiador? = datos[id]

    override suspend fun obtenerTodos(): List<Acopiador> = datos.values.toList()

    override suspend fun guardar(acopiador: Acopiador): Acopiador {
        if (fallarAlGuardar) error("Fallo controlado")
        datos[acopiador.id] = acopiador
        return acopiador
    }
}

private class FakeAuditoriaRepository : AuditoriaRepository {
    private val registros = mutableListOf<RegistroAuditoria>()
    override suspend fun obtenerPorId(id: String): RegistroAuditoria? = registros.find { it.id == id }
    override suspend fun guardar(registro: RegistroAuditoria): RegistroAuditoria = registro.also { registros += it }
    override fun observarTodos() = kotlinx.coroutines.flow.flowOf(registros.toList())
}
