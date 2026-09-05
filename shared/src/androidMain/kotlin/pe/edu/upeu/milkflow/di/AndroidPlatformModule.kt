package pe.edu.upeu.milkflow.di

import android.content.Context
import pe.edu.upeu.milkflow.data.local.AndroidDatabaseDriverFactory

fun initKoinAndroid(
    context: Context,
    validarClave: ValidadorClave = { _, _ -> false },
) {
    initKoin(
        platformModule = platformModule(AndroidDatabaseDriverFactory(context)),
        validarClave = validarClave,
    )
}
