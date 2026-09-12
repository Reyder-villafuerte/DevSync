@extends('layouts.panel')
@section('titulo', 'Jefatura de Planta')
@section('contenido')
    <h1>Jefatura de Planta</h1>

    {{-- Arriba, lo que el jefe mira primero: cómo va el turno y la ruta que
         hay que recibir. Debajo, el detalle entrega a entrega y la producción. --}}
    <div class="rejilla dos desigual">
        @livewire('panel.recepcion.semaforo-rendimiento')
        @livewire('panel.recepcion.recepcion-dia')
    </div>

    @livewire('panel.recepcion.verificacion-entregas')
    @livewire('panel.recepcion.sesiones-produccion')
@endsection
