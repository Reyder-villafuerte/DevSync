@echo off
cd /d "%~dp0"
echo ========================================
echo   MilkFlow - Servidor Backend Laravel
echo ========================================
echo.

echo Activando tunel ADB para el emulador Android...
"C:\Users\ASUS\AppData\Local\Android\Sdk\platform-tools\adb.exe" reverse tcp:8000 tcp:8000 2>nul && (
    echo [OK] adb reverse activo - el emulador puede conectar a 127.0.0.1:8000
) || (
    echo [!] No se pudo activar adb reverse - asegurate de tener el emulador abierto
)
echo.

echo Iniciando servidor Laravel...
echo.
C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe artisan serve --host=0.0.0.0
echo.
echo El servidor se detuvo.
pause
