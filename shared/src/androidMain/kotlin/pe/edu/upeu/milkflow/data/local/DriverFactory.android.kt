package pe.edu.upeu.milkflow.data.local

import androidx.sqlite.db.SupportSQLiteDatabase
import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.android.AndroidSqliteDriver
import pe.edu.upeu.milkflow.data.local.db.MilkFlowDatabase

class DriverFactoryAndroid(private val context: android.content.Context) : DriverFactory {
    override fun crear(): SqlDriver = AndroidSqliteDriver(
        schema = MilkFlowDatabase.Schema,
        context = context,
        name = "milkflow.db",
        callback = object : AndroidSqliteDriver.Callback(MilkFlowDatabase.Schema) {
            override fun onOpen(db: SupportSQLiteDatabase) {
                db.execSQL("PRAGMA foreign_keys=ON;")
            }

            /**
             * La BD local es SÓLO CACHÉ de sincronización. Si el dispositivo trae
             * un `milkflow.db` de un esquema más nuevo (build anterior o cambio de
             * rama), en vez de crashear se recrea desde cero: la próxima
             * sincronización la vuelve a poblar desde el backend.
             */
            override fun onDowngrade(db: SupportSQLiteDatabase, oldVersion: Int, newVersion: Int) {
                db.query(
                    "SELECT name FROM sqlite_master WHERE type='table' " +
                        "AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'android_%'",
                ).use { cursor ->
                    val tablas = buildList { while (cursor.moveToNext()) add(cursor.getString(0)) }
                    tablas.forEach { db.execSQL("DROP TABLE IF EXISTS `$it`") }
                }
                onCreate(db)
            }
        },
    )
}
