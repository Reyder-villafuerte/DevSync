package pe.edu.upeu.milkflow.domain

import kotlinx.coroutines.test.runTest
import kotlin.time.Instant
import kotlinx.datetime.LocalDate
import pe.edu.upeu.milkflow.core.ErrorApp
import pe.edu.upeu.milkflow.core.Resultado
import pe.edu.upeu.milkflow.domain.model.EstadoJornada
import pe.edu.upeu.milkflow.domain.model.JornadaRuta
import pe.edu.upeu.milkflow.domain.usecase.IniciarJornadaUseCase
import pe.edu.upeu.milkflow.domain.vo.Litros
import pe.edu.upeu.milkflow.fakes.JornadaRepositoryFake
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs

class IniciarJornadaUseCaseTest {

    private fun jornada(id: String, estado: EstadoJornada) = JornadaRuta(
        id = id, acopiadorId = "acop1", rutaId = "r1", dispositivoId = null,
        fecha = LocalDate(2026, 9, 10),
        horaInicio = Instant.parse("2026-09-10T10:00:00Z"),
        horaCierre = null, litrosDeclarados = Litros.CERO, estado = estado,
        updatedAt = Instant.parse("2026-09-10T10:00:00Z"), version = 0, deleted = false,
    )

    @Test
    fun sin_jornada_previa_abre_una_nueva() = runTest {
        val repo = JornadaRepositoryFake()
        val r = IniciarJornadaUseCase(repo)("acop1", "r1")
        assertIs<Resultado.Exito<JornadaRuta>>(r)
        assertEquals(EstadoJornada.EN_CURSO, r.valor.estado)
    }

    @Test
    fun con_jornada_en_curso_es_idempotente() = runTest {
        val enCurso = jornada("j1", EstadoJornada.EN_CURSO)
        val r = IniciarJornadaUseCase(JornadaRepositoryFake(activa = enCurso))("acop1", "r1")
        assertIs<Resultado.Exito<JornadaRuta>>(r)
        assertEquals("j1", r.valor.id)
    }

    /**
     * Con la regla activa (una jornada por acopiador y día) no se abre una
     * segunda: el servidor la rechazaría por su índice único y arrastraría a
     * las recolecciones que colgaran de ella.
     */
    @Test
    fun con_la_regla_activa_y_la_jornada_de_hoy_conciliada_no_abre_otra() = runTest {
        val repo = JornadaRepositoryFake(deHoy = jornada("j1", EstadoJornada.CONCILIADA))
        val r = IniciarJornadaUseCase(repo, unaJornadaPorDia = true)("acop1", "r1")
        assertIs<Resultado.Fallo>(r)
        assertEquals("jornada_del_dia_ya_cerrada", assertIs<ErrorApp.ReglaNegocio>(r.error).regla)
        assertEquals(null, repo.activa)
    }

    /** Fase de pruebas (regla apagada): se puede repetir el ciclo el mismo día. */
    @Test
    fun con_la_regla_apagada_abre_otra_jornada_el_mismo_dia() = runTest {
        val repo = JornadaRepositoryFake(deHoy = jornada("j1", EstadoJornada.CERRADA))
        val r = IniciarJornadaUseCase(repo)("acop1", "r1")
        assertIs<Resultado.Exito<JornadaRuta>>(r)
        assertEquals(EstadoJornada.EN_CURSO, r.valor.estado)
    }
}
