plugins {
    alias(libs.plugins.androidApplication)
    // Compilador de Compose (Kotlin 2.x lo trae como plugin aparte). NO se aplica
    // `org.jetbrains.kotlin.android`: AGP 9 ya integra Kotlin.
    alias(libs.plugins.composeCompiler)
}

android {
    namespace = "pe.edu.upeu.milkflow"
    compileSdk = libs.versions.android.compileSdk.get().toInt()

    defaultConfig {
        applicationId = "pe.edu.upeu.milkflow"
        minSdk = libs.versions.android.minSdk.get().toInt()
        targetSdk = libs.versions.android.targetSdk.get().toInt()
        versionCode = 1
        versionName = "1.0"
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    // AGP 9 trae soporte de Kotlin integrado (no se aplica kotlin.android).
    kotlin {
        jvmToolchain(17)
    }

    testOptions {
        unitTests.isReturnDefaultValues = true
    }
}

dependencies {
    implementation(project(":shared"))

    // Jetpack Compose (artefactos JetBrains Compose Multiplatform, ya en el catálogo).
    implementation(libs.compose.runtime)
    implementation(libs.compose.foundation)
    implementation(libs.compose.ui)
    implementation(libs.compose.material3)
    implementation(libs.androidx.navigation.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.activity.compose)

    // DI en Compose.
    implementation(libs.koin.android)
    implementation(libs.koin.androidx.compose)

    implementation(libs.androidx.work.runtime)
    implementation(libs.kotlinx.coroutines.android)
    // El dominio de `shared` expone tipos de kotlinx-datetime (LocalDate, Instant vía
    // kotlin.time) en sus firmas públicas; `shared` los declara `implementation`, así
    // que el consumidor debe traerlos.
    implementation(libs.kotlinx.datetime)

    testImplementation(kotlin("test-junit"))
    testImplementation(libs.kotlinx.coroutines.test)
    testImplementation(libs.turbine)
    testImplementation(libs.koin.test)
}
