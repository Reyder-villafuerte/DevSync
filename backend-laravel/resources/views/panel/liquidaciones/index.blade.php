@extends('layouts.panel')
@section('titulo', 'Liquidaciones semanales')
@section('contenido')
    <h1>Liquidaciones semanales</h1>
    <table>
        <thead><tr><th>Semana</th><th>Liquidación (viernes)</th><th>Estado</th><th># Liquidaciones</th><th></th></tr></thead>
        <tbody>
        @foreach ($semanas as $s)
            <tr>
                <td>{{ $s->fecha_inicio->format('d/m/Y') }} – {{ $s->fecha_fin->format('d/m/Y') }}</td>
                <td>{{ $s->fecha_liquidacion->format('d/m/Y') }}</td>
                <td>{{ $s->estado->value }}</td>
                <td>{{ $s->liquidaciones_count }}</td>
                <td><a href="{{ route('liquidaciones.show', $s) }}">detalle</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $semanas->links() }}
@endsection
