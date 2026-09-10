package pe.edu.upeu.milkflow

import android.os.Bundle
import android.text.method.ScrollingMovementMethod
import android.widget.TextView
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import pe.edu.upeu.milkflow.ui.navigation.MilkFlowApp
import pe.edu.upeu.milkflow.ui.theme.MilkFlowTheme

/**
 * Host de navegación real de la app. Toda la UI es Compose + Material 3; la
 * navegación por rol vive en [MilkFlowApp] y el estado en los ViewModels.
 */
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Si la ejecución anterior crasheó, mostramos el stack trace en pantalla
        // (pantalla plana, sin Compose, para que no falle igual) en lugar de la app.
        val crashPrevio = CazadorDeCrashes.leerYBorrar(this)
        if (crashPrevio != null) {
            setContentView(
                TextView(this).apply {
                    text = "MilkFlow no pudo abrir en el intento anterior.\n" +
                        "Copia o captura este texto y envíalo:\n\n$crashPrevio"
                    textSize = 11f
                    setPadding(28, 56, 28, 28)
                    setTextIsSelectable(true)
                    movementMethod = ScrollingMovementMethod()
                },
            )
            return
        }

        setContent {
            MilkFlowTheme {
                MilkFlowApp()
            }
        }
    }
}
