package pe.edu.upeu.milkflow.infra.conectividad

import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.NetworkRequest
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.flow.callbackFlow
import pe.edu.upeu.milkflow.domain.sync.ObservadorConectividad

/**
 * Conectividad vía ConnectivityManager. Emite `true` cuando hay al menos una
 * red con capacidad de internet validada.
 */
class ObservadorConectividadAndroid(context: Context) : ObservadorConectividad {

    private val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager

    override val enLinea: Flow<Boolean> = callbackFlow {
        fun hayInternet(): Boolean {
            val red = cm.activeNetwork ?: return false
            val caps = cm.getNetworkCapabilities(red) ?: return false
            return caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
                caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
        }

        val callback = object : ConnectivityManager.NetworkCallback() {
            override fun onAvailable(network: Network) { trySend(hayInternet()) }
            override fun onLost(network: Network) { trySend(hayInternet()) }
            override fun onCapabilitiesChanged(network: Network, caps: NetworkCapabilities) { trySend(hayInternet()) }
        }

        val request = NetworkRequest.Builder()
            .addCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
            .build()

        cm.registerNetworkCallback(request, callback)
        trySend(hayInternet())          // estado inicial
        awaitClose { cm.unregisterNetworkCallback(callback) }
    }.distinctUntilChanged()
}
