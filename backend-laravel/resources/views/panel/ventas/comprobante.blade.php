@extends('layouts.panel')
@section('titulo', 'Comprobante '.$venta->comprobante())
@section('contenido')
    <div class="comprobante">
        <h2>MilkFlow</h2>
        <p class="tenue" style="text-align:center;margin:.2rem 0">Asociación de productores lecheros</p>
        <div class="numero">{{ strtoupper($venta->tipo_comprobante) }} {{ $venta->comprobante() }}</div>

        <p>
            <strong>Fecha:</strong> {{ $venta->fecha->format('d/m/Y') }}<br>
            <strong>Cliente:</strong> {{ $venta->cliente?->nombre }} ({{ $venta->cliente?->tipo_cliente->value }})
        </p>

        <table>
            <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num">P. unit.</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
            @foreach ($venta->detalles as $d)
                <tr>
                    <td>{{ $d->producto?->nombre }}</td>
                    <td class="num">{{ $d->cantidad }}</td>
                    <td class="num">{{ number_format((float) $d->precio_unitario, 2) }}</td>
                    <td class="num">{{ number_format((float) $d->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="total">TOTAL: S/ {{ number_format((float) $venta->total, 2) }}</div>

        <p class="no-imprimir" style="text-align:center;margin-top:1.2rem">
            <button onclick="window.print()">Imprimir</button>
        </p>
    </div>
@endsection
