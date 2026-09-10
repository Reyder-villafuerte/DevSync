<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MilkFlow · @yield('titulo', 'Panel')</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    @livewireStyles
</head>
<body>
<div class="app">
    @auth
        <aside class="barra-lateral no-imprimir">
            <div class="marca">🥛 MilkFlow</div>
            <nav>
                <a href="{{ route('panel.inicio') }}" @class(['activo' => request()->routeIs('panel.inicio')])>Inicio</a>

                @can('panel-administracion')
                    <a href="{{ route('panel.administracion', ['tab' => 'tarifas']) }}" @class(['activo' => request()->routeIs('panel.administracion') && request('tab', 'tarifas') === 'tarifas'])>Tarifas</a>
                    <a href="{{ route('panel.administracion', ['tab' => 'liquidacion']) }}" @class(['activo' => request()->routeIs('panel.administracion') && request('tab') === 'liquidacion'])>Liquidación del viernes</a>
                    <a href="{{ route('panel.administracion', ['tab' => 'asamblea']) }}" @class(['activo' => request()->routeIs('panel.administracion') && request('tab') === 'asamblea'])>Asistencia a asamblea</a>
                    <a href="{{ route('panel.administracion', ['tab' => 'avisos']) }}" @class(['activo' => request()->routeIs('panel.administracion') && request('tab') === 'avisos'])>Avisos</a>
                    <a href="{{ route('panel.administracion', ['tab' => 'usuarios']) }}" @class(['activo' => request()->routeIs('panel.administracion') && request('tab') === 'usuarios'])>Usuarios</a>
                    <a href="{{ route('panel.administracion', ['tab' => 'solicitudes']) }}" @class(['activo' => request()->routeIs('panel.administracion') && request('tab') === 'solicitudes'])>Solicitudes de cambio de ruta</a>
                    <a href="{{ route('liquidaciones.index') }}" @class(['activo' => request()->routeIs('liquidaciones.*')])>Liquidaciones</a>
                @endcan
            </nav>
            <div class="pie">
                <div>{{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}</div>
                <div class="tenue">{{ auth()->user()->rol->etiqueta() }}</div>
                <form method="POST" action="{{ route('panel.logout') }}">@csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>
        </aside>
    @endauth

    <main class="contenido">
        @if (session('ok')) <p class="flash ok">{{ session('ok') }}</p> @endif
        @if (session('error')) <p class="flash error">{{ session('error') }}</p> @endif

        @yield('contenido')
    </main>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
