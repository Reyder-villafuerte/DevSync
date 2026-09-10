package pe.edu.upeu.milkflow.ui.util

import android.content.Context
import android.graphics.Canvas
import android.graphics.Paint
import android.graphics.Typeface
import android.graphics.pdf.PdfDocument
import android.os.Bundle
import android.os.CancellationSignal
import android.os.ParcelFileDescriptor
import android.print.PageRange
import android.print.PrintAttributes
import android.print.PrintDocumentAdapter
import android.print.PrintDocumentInfo
import android.print.PrintManager
import androidx.core.content.FileProvider
import android.content.Intent
import android.net.Uri
import java.io.File
import java.io.FileOutputStream

/**
 * Impresión con el framework de Android (sin librerías). El comprobante de
 * cierre de ruta y el ticket térmico de inspección se envían al diálogo de
 * impresión del sistema; el acta se exporta a un PDF en cache y se abre con un
 * visor externo vía FileProvider.
 *
 * Formato de página angosto (~58mm ≈ 164pt) para que se vea como un ticket
 * térmico; se rellena de arriba a abajo con líneas de texto.
 */
object Impresion {

    private const val ANCHO_TICKET_PT = 190
    private const val MARGEN = 12f

    fun imprimir(context: Context, titulo: String, lineas: List<String>) {
        val printManager = context.getSystemService(Context.PRINT_SERVICE) as PrintManager
        printManager.print(titulo, AdaptadorTicket(titulo, lineas), null)
    }

    /** @return Uri content:// del PDF generado, listo para ACTION_VIEW. */
    fun exportarPdf(context: Context, nombre: String, titulo: String, lineas: List<String>): Uri {
        val dir = File(context.cacheDir, "actas").apply { mkdirs() }
        val archivo = File(dir, "$nombre.pdf")

        val doc = PdfDocument()
        val ancho = 420  // A5 aprox. en puntos para un acta legible
        var pagina = doc.startPage(PdfDocument.PageInfo.Builder(ancho, 620, 1).create())
        dibujar(pagina.canvas, ancho, titulo, lineas)
        doc.finishPage(pagina)
        FileOutputStream(archivo).use { doc.writeTo(it) }
        doc.close()

        return FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", archivo)
    }

    fun abrirPdf(context: Context, uri: Uri) {
        context.startActivity(
            Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(uri, "application/pdf")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
            },
        )
    }

    private fun dibujar(canvas: Canvas, ancho: Int, titulo: String, lineas: List<String>) {
        val negro = Paint().apply { color = 0xFF000000.toInt() }
        val tituloPaint = Paint(negro).apply { textSize = 15f; typeface = Typeface.DEFAULT_BOLD }
        val cuerpo = Paint(negro).apply { textSize = 12f }
        var y = MARGEN + 16f
        canvas.drawText(titulo, MARGEN, y, tituloPaint)
        y += 10f
        canvas.drawLine(MARGEN, y, ancho - MARGEN, y, negro)
        y += 18f
        lineas.forEach { linea ->
            canvas.drawText(linea, MARGEN, y, cuerpo)
            y += 18f
        }
    }

    private class AdaptadorTicket(
        private val nombre: String,
        private val lineas: List<String>,
    ) : PrintDocumentAdapter() {

        override fun onLayout(
            oldAttributes: PrintAttributes?,
            newAttributes: PrintAttributes,
            cancellationSignal: CancellationSignal?,
            callback: LayoutResultCallback,
            extras: Bundle?,
        ) {
            if (cancellationSignal?.isCanceled == true) {
                callback.onLayoutCancelled()
                return
            }
            val info = PrintDocumentInfo.Builder("$nombre.pdf")
                .setContentType(PrintDocumentInfo.CONTENT_TYPE_DOCUMENT)
                .setPageCount(1)
                .build()
            callback.onLayoutFinished(info, true)
        }

        override fun onWrite(
            pages: Array<out PageRange>?,
            destination: ParcelFileDescriptor,
            cancellationSignal: CancellationSignal?,
            callback: WriteResultCallback,
        ) {
            val alto = 40 + lineas.size * 18 + 40
            val doc = PdfDocument()
            val pagina = doc.startPage(PdfDocument.PageInfo.Builder(ANCHO_TICKET_PT, alto, 1).create())
            dibujar(pagina.canvas, ANCHO_TICKET_PT, nombre, lineas)
            doc.finishPage(pagina)
            FileOutputStream(destination.fileDescriptor).use { doc.writeTo(it) }
            doc.close()
            callback.onWriteFinished(arrayOf(PageRange.ALL_PAGES))
        }
    }
}
