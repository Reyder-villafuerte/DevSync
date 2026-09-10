package pe.edu.upeu.milkflow.ui

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.setMain
import kotlin.test.AfterTest
import kotlin.test.BeforeTest

@OptIn(ExperimentalCoroutinesApi::class)
abstract class VmTestBase {
    protected val dispatcher = StandardTestDispatcher()

    @BeforeTest
    fun instalarMain() {
        Dispatchers.setMain(dispatcher)
    }

    @AfterTest
    fun quitarMain() {
        Dispatchers.resetMain()
    }
}
