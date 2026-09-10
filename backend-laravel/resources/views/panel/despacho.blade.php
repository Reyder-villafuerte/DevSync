@extends('layouts.panel')
@section('titulo', 'Despacho y Ventas')
@section('contenido')
    <h1>Despacho y Ventas</h1>

    <div class="rejilla dos">
        @livewire('panel.despacho.stock-disponible')
        @livewire('panel.despacho.punto-de-venta')
    </div>
@endsection
