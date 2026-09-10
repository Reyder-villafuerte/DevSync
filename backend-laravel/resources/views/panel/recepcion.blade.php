@extends('layouts.panel')
@section('titulo', 'Jefatura de Planta')
@section('contenido')
    <h1>Jefatura de Planta</h1>

    @livewire('panel.recepcion.semaforo-rendimiento')
    @livewire('panel.recepcion.verificacion-entregas')
    @livewire('panel.recepcion.recepcion-dia')
    @livewire('panel.recepcion.sesiones-produccion')
@endsection
