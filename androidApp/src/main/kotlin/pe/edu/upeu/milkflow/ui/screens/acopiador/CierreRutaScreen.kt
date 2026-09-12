@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package pe.edu.upeu.milkflow.ui.screens.acopiador

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import org.koin.androidx.compose.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.edu.upeu.milkflow.domain.repository.SesionActiva
import pe.edu.upeu.milkflow.ui.components.BotonPrincipal
import pe.edu.upeu.milkflow.ui.components.EstadoVacio
import pe.edu.upeu.milkflow.ui.components.Rotulo
import pe.edu.upeu.milkflow.ui.components.TarjetaKpi
import pe.edu.upeu.milkflow.ui.components.objetivoTactil
import pe.edu.upeu.milkflow.ui.theme.Dimens
import pe.edu.upeu.milkflow.ui.theme.LocalColoresMilkFlow
import pe.edu.upeu.milkflow.ui.util.litros

@Composable
fun CierreRutaScreen(
    sesion: SesionActiva,
    onVolver: () -> Unit,
    onComprobante: (String) -> Unit,
    vm: CierreRutaViewModel = koinViewModel { parametersOf(sesion) },
) {
    val s by vm.estado.collectAsState()

    // La navegación al comprobante se dispara desde el estado (efecto de una sola vez).
    LaunchedEffect(s.cerrada) {
        s.cerrada?.let(onComprobante)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Cierre de ruta") },
                navigationIcon = {
                    TextButton(onClick = onVolver, modifier = Modifier.objetivoTactil()) { Text("Volver") }
                },
            )
        },
    ) { pad ->
        Column(
            Modifier.fillMaxSize().padding(pad).padding(Dimens.EspacioM).verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(Dimens.EspacioM),
        ) {
            // Tras cerrar, la jornada deja de estar activa mientras se navega al
            // comprobante: no hay que anunciar que "no hay ruta" en ese instante.
            if (!s.hayJornada) {
                EstadoVacio(
                    titulo = if (s.cerrada != null) "Ruta cerrada" else "No hay una ruta abierta",
                    mensaje = if (s.cerrada != null) {
                        "Abriendo el comprobante…"
                    } else {
                        "Vuelva a la lista de paradas para iniciar la ruta del día."
                    },
                )
                return@Column
            }

            Rotulo("Resumen del turno")
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Dimens.EspacioS)) {
                TarjetaKpi("Litros", litros(s.litros), Modifier.weight(1f))
                TarjetaKpi(
                    "Socios",
                    "${s.socios}",
                    Modifier.weight(1f),
                    acento = LocalColoresMilkFlow.current.conforme,
                )
                TarjetaKpi(
                    "Pendientes",
                    "${s.paradasPendientes}",
                    Modifier.weight(1f),
                    acento = if (s.paradasPendientes > 0) {
                        LocalColoresMilkFlow.current.accion
                    } else {
                        LocalColoresMilkFlow.current.conforme
                    },
                )
            }

            if (s.paradasPendientes > 0) {
                AvisoEnLinea(
                    "Quedan ${s.paradasPendientes} parada(s) sin recolección. Aun así puede cerrar la ruta.",
                    LocalColoresMilkFlow.current.accion,
                    LocalColoresMilkFlow.current.accionSuave,
                )
            }

            if (s.error != null) {
                AvisoEnLinea(
                    s.error!!,
                    LocalColoresMilkFlow.current.alerta,
                    LocalColoresMilkFlow.current.alertaSuave,
                )
            }

            Text(
                "Al confirmar se registra la hora de cierre y los litros declarados. " +
                    "El comprobante queda disponible aunque no haya señal.",
                style = MaterialTheme.typography.bodyMedium,
                color = LocalColoresMilkFlow.current.tintaSuave,
            )

            BotonPrincipal(
                texto = if (s.cerrando) "Cerrando…" else "Confirmar cierre de ruta",
                onClick = vm::confirmarCierre,
                habilitado = !s.cerrando,
                anchoCompleto = true,
            )
        }
    }
}

/** Aviso en línea con fondo tenue: se ve sin competir con la acción principal. */
@Composable
private fun AvisoEnLinea(texto: String, tinta: Color, fondo: Color) {
    Text(
        texto,
        color = tinta,
        style = MaterialTheme.typography.bodyMedium,
        modifier = Modifier
            .fillMaxWidth()
            .background(fondo, Dimens.FormaTarjeta)
            .padding(Dimens.EspacioM),
    )
}
