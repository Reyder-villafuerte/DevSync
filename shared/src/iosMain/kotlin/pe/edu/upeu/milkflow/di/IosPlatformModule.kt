package pe.edu.upeu.milkflow.di

import pe.edu.upeu.milkflow.data.local.IosDatabaseDriverFactory

fun initKoinIos(
    validarClave: ValidadorClave = { _, _ -> false },
) {
    initKoin(
        platformModule = platformModule(IosDatabaseDriverFactory()),
        validarClave = validarClave,
    )
}
