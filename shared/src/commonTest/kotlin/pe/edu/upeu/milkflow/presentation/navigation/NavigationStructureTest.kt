package pe.edu.upeu.milkflow.presentation.navigation

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse

class NavigationStructureTest {
    @Test
    fun todasLasRutasSonUnicas() {
        val routes = AppDestination.all.map(AppDestination::route)

        assertEquals(routes.size, routes.distinct().size)
    }

    @Test
    fun soloLasCincoSeccionesPrincipalesUsanBarraInferior() {
        val bottomRoutes = AppDestination.all
            .filter(AppDestination::isBottomDestination)
            .map(AppDestination::route)

        assertEquals(
            listOf("inicio", "entregas", "reportes", "sincronizacion", "perfil"),
            bottomRoutes,
        )
        assertFalse(AppDestination.Login.isBottomDestination)
    }
}
