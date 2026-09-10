package pe.edu.upeu.milkflow.infra.almacen

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

/**
 * Token de Sanctum en EncryptedSharedPreferences. La clave maestra vive en el
 * Android Keystore; el archivo de preferencias queda cifrado en disco.
 */
class AlmacenSeguroTokenAndroid(context: Context) : AlmacenSeguroToken {

    private val prefs: SharedPreferences by lazy {
        val clave = MasterKey.Builder(context)
            .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
            .build()
        EncryptedSharedPreferences.create(
            context,
            "milkflow_sesion",
            clave,
            EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
        )
    }

    override suspend fun guardar(token: String) = withContext(Dispatchers.IO) {
        prefs.edit().putString(CLAVE_TOKEN, token).apply()
    }

    override suspend fun leer(): String? = withContext(Dispatchers.IO) {
        prefs.getString(CLAVE_TOKEN, null)
    }

    override suspend fun borrar() = withContext(Dispatchers.IO) {
        prefs.edit().remove(CLAVE_TOKEN).apply()
    }

    private companion object {
        const val CLAVE_TOKEN = "token_sanctum"
    }
}
