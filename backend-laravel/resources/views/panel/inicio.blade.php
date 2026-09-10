@extends('layouts.panel')
@section('titulo', 'Inicio')
@section('contenido')
    <h1>Bienvenido, {{ auth()->user()->nombres }}</h1>
    <p class="tenue">Rol activo: <strong>{{ $rol->etiqueta() }}</strong></p>

    <div class="tarjeta">
        <h2>Sus paneles</h2>
        <ul>
            @can('panel-jefatura-planta')
                <li><a href="{{ route('panel.recepcion') }}">Jefatura de Planta</a> — recepción del día, sesiones de producción y semáforo de rendimiento (RN-08).</li>
            @endcan
            @can('panel-despacho')
                <li><a href="{{ route('panel.despacho') }}">Despacho y Ventas</a> — stock disponible y punto de venta.</li>
            @endcan
            @can('panel-administracion')
                <li><a href="{{ route('panel.administracion') }}">Administración y Gerencia</a> — tarifas, liquidaciones, asamblea, avisos, usuarios y solicitudes.</li>
            @endcan
        </ul>
    </div>
@endsection
