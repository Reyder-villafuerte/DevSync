@extends('layouts.app')

@section('title', 'Recibo ' . $sale->receipt_number)

@section('content')
<div class="max-w-xl mx-auto space-y-4">
    <div class="no-print flex justify-between items-center">
        @if(Auth::check() && Auth::user()->role === 'productor')
        <a href="{{ route('productor.descuentos') }}" class="text-xs font-bold text-slate-500 hover:text-slate-900 flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> <span>Volver a Descuentos</span>
        </a>
        @else
        <a href="{{ route('ventas.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-900 flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> <span>Volver a Ventas</span>
        </a>
        @endif
        <button onclick="window.print()" class="bg-spark-dark hover:bg-black text-spark-lime px-4 py-2 rounded-xl text-xs font-bold shadow-sm flex items-center gap-1.5 transition">
            <i class="fa-solid fa-print"></i> <span>Imprimir Recibo</span>
        </button>
    </div>

    <!-- TARJETA RECIBO SPARK STYLE -->
    <div class="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-md font-mono text-slate-800 text-xs">
        <div class="text-center pb-6 border-b border-dashed border-slate-300">
            <div class="w-10 h-10 rounded-xl bg-spark-dark text-spark-lime mx-auto flex items-center justify-center text-lg mb-2">
                <i class="fa-solid fa-asterisk"></i>
            </div>
            <h2 class="text-base font-black tracking-wider uppercase text-slate-900 font-sans">PLANTA QUESERA HUATA</h2>
            <p class="text-[11px] text-slate-500 font-sans">Distrito de Huata — Puno, Perú</p>
            <div class="mt-3 inline-block bg-slate-100 px-3 py-1 rounded-full text-slate-800 font-bold text-xs">
                RECIBO: {{ $sale->receipt_number }}
            </div>
        </div>

        <div class="py-4 border-b border-dashed border-slate-300 space-y-1.5">
            <div class="flex justify-between">
                <span class="text-slate-400">Fecha y Hora:</span>
                <strong class="text-slate-800">{{ $sale->sold_at }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Despachador:</span>
                <span>{{ $sale->seller->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Cliente:</span>
                <strong class="text-slate-900">{{ $sale->customer->first_name }} {{ $sale->customer->last_name }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Categoría:</span>
                <span class="uppercase font-bold text-emerald-800">{{ $sale->customer->type }}</span>
            </div>
            @if($sale->customer->dni_ruc)
            <div class="flex justify-between">
                <span class="text-slate-400">DNI / RUC:</span>
                <span>{{ $sale->customer->dni_ruc }}</span>
            </div>
            @endif
        </div>

        <div class="py-4 border-b border-dashed border-slate-300">
            <table class="w-full">
                <thead>
                    <tr class="text-[10px] text-slate-400 border-b border-slate-200">
                        <th class="text-left pb-1.5">ÍTEM</th>
                        <th class="text-center pb-1.5">CANT</th>
                        <th class="text-right pb-1.5">P.UNIT</th>
                        <th class="text-right pb-1.5">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-2.5">Molde Queso Madurado Huata</td>
                        <td class="text-center py-2.5 font-black">{{ $sale->cheese_molds_quantity }}</td>
                        <td class="text-right py-2.5">S/ {{ number_format($sale->unit_price, 2) }}</td>
                        <td class="text-right py-2.5 font-bold">S/ {{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pt-4 space-y-1">
            <div class="flex justify-between text-base font-black text-slate-900">
                <span>TOTAL COBRADO:</span>
                <span class="text-emerald-700">S/ {{ number_format($sale->total_amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-[11px] text-slate-500">
                <span>MÉTODO:</span>
                <strong class="uppercase text-slate-800">{{ $sale->payment_method }} (Efectivo contra entrega)</strong>
            </div>
        </div>

        <div class="mt-8 pt-4 border-t border-slate-100 text-center text-[10px] text-slate-400 font-sans">
            ¡Gracias por preferir la calidad de los ganaderos de Huata!
        </div>
    </div>
</div>
@endsection
