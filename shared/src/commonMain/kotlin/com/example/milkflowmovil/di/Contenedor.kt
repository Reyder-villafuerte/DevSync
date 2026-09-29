package com.example.milkflowmovil.di

import com.example.milkflowmovil.datos.Repositorio
import com.example.milkflowmovil.datos.Sincronizador
import com.example.milkflowmovil.datos.local.BaseLocal
import com.example.milkflowmovil.datos.remoto.ApiMilkFlow
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob

/**
 * Armado manual de las piezas de la app.
 *
 * Son cuatro objetos con dependencias claras; una librería de inyección solo
 * añadiría configuración que alguien tendría que mantener.
 */
class Contenedor {
    private val alcance = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    val base = BaseLocal(alcance = alcance)
    private val api = ApiMilkFlow { base.actual.urlBase }
    private val sincronizador = Sincronizador(base, api)

    val repositorio = Repositorio(base, api, sincronizador, alcance)
}
