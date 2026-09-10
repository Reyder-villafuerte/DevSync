@extends('layouts.panel')
@section('titulo', 'Detalle de liquidación')
@section('contenido')
    <h1>Semana {{ $semana->fecha_inicio->format('d/m/Y') }} – {{ $semana->fecha_fin->format('d/m/Y') }}</h1>
    <p>Estado: <strong>{{ $semana->estado->value }}</strong> · Liquidada: {{ optional($semana->liquidada_en)->format('d/m/Y H:i') ?? '—' }}</p>

    <table>
        <thead><tr><th>Productor</th><th>Litros</th><th>S/ litro</th><th>Bruto</th><th>Descuentos</th><th>Neto</th><th>Tarifa</th></tr></thead>
        <tbody>
        @foreach ($semana->liquidaciones as $l)
            <tr>
                <td>{{ $l->productor->nombres }} {{ $l->productor->apellidos }}</td>
                <td>{{ $l->litros_totales }}</td>
                <td>{{ $l->precio_litro_aplicado }}</td>
                <td>{{ number_format($l->monto_bruto, 2) }}</td>
                <td>{{ number_format($l->total_descuentos, 2) }}</td>
                <td><strong>{{ number_format($l->monto_neto, 2) }}</strong></td>
                <td>{{ $l->tarifa_degradada ? 'MÍNIMA (RN-05)' : 'normal' }}</td>
            </tr>
            @foreach ($l->detalles as $d)
                <tr style="color:#64748b;font-size:.9rem">
                    <td colspan="2">&nbsp;&nbsp;↳ {{ $d->concepto }}</td>
                    <td colspan="4">{{ $d->descripcion }}</td>
                    <td>{{ number_format($d->monto, 2) }}</td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
        <tfoot>
            <tr><th colspan="5">Total neto de la semana</th><th colspan="2">S/ {{ number_format($semana->liquidaciones->sum('monto_neto'), 2) }}</th></tr>
        </tfoot>
    </table>
@endsection
