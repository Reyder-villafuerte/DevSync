@extends('layouts.panel')
@section('titulo', 'Stock')
@section('contenido')
    <h1>Stock de planta</h1>

    @livewire('panel.stock.panel-stock')
@endsection
