@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-6">
    <div class="mb-4 flex items-center justify-between print:hidden">
        <a href="{{ route('pagos.ruta.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-slate-900 transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Volver a la Planilla de Sobres
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('pagos.ruta.history') }}" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
                Ver Historial de Pagos
            </a>
            <button onclick="window.print()" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition shadow flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Imprimir Recibo
            </button>
        </div>
    </div>

    <!-- Comprobante / Recibo Térmico de Sobre -->
    <div class="bg-white border-2 border-slate-300 rounded-2xl p-6 sm:p-8 shadow-md print:border-none print:shadow-none print:p-2">
        <div class="text-center pb-5 border-b border-dashed border-slate-300">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-emerald-50 text-emerald-700 rounded-2xl mb-2 font-black text-xl">
                🥛
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase">Planta de Lácteos - Huata</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Recibo de Liquidación Semanal en Sobre de Efectivo</p>
            <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-1 bg-slate-100 rounded-full text-xs font-semibold text-slate-700">
                <span>Ruta Viernes</span> • <span>Folio: #SOBRE-{{ str_pad($settlement->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>

        <div class="py-4 border-b border-dashed border-slate-300 grid grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-500 block uppercase font-medium">Productor</span>
                <span class="text-slate-900 font-bold text-sm block">{{ $settlement->producer->name }}</span>
                <span class="text-slate-500 text-xs">DNI: {{ $settlement->producer->dni ?? 'N/A' }} | Cód: {{ $settlement->producer->producer_code ?? 'PR-' . $settlement->producer->id }}</span>
            </div>
            <div class="text-right">
                <span class="text-slate-500 block uppercase font-medium">Zona de Acopio</span>
                <span class="text-slate-900 font-bold text-sm block">{{ $settlement->producer->zone?->name ?? 'Comunidad Huata' }}</span>
                <span class="text-slate-500 text-xs">Ruta de Acopio Viernes</span>
            </div>
            <div>
                <span class="text-slate-500 block uppercase font-medium">Ciclo Semanal</span>
                <span class="text-slate-800 font-semibold">
                    {{ $settlement->start_date ? \Carbon\Carbon::parse($settlement->start_date)->format('d/m/Y') : '-' }} al {{ $settlement->end_date ? \Carbon\Carbon::parse($settlement->end_date)->format('d/m/Y') : '-' }}
                </span>
                <span class="text-xs text-emerald-700 font-medium block">Cierre Miércoles / Sobre Jueves</span>
            </div>
            <div class="text-right">
                <span class="text-slate-500 block uppercase font-medium">Fecha y Hora de Pago</span>
                <span class="text-slate-800 font-semibold">{{ $settlement->paid_at ? \Carbon\Carbon::parse($settlement->paid_at)->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
                <span class="text-xs text-slate-500 block">Pagador: {{ $settlement->payer->name ?? Auth::user()->name }}</span>
            </div>
        </div>

        <div class="py-4 border-b border-dashed border-slate-300">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Detalle de la Liquidación</h3>
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-100">
                        <th class="text-left pb-2 font-medium">Concepto</th>
                        <th class="text-center pb-2 font-medium">Cant. / Litros</th>
                        <th class="text-right pb-2 font-medium">Precio</th>
                        <th class="text-right pb-2 font-medium">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-2.5 font-semibold text-slate-800">
                            Litros de leche acopiados
                            <span class="block text-[11px] font-normal text-slate-500">Registro en ruta por acopiador</span>
                        </td>
                        <td class="py-2.5 text-center font-bold text-slate-900">{{ number_format($settlement->total_liters, 2) }} L</td>
                        <td class="py-2.5 text-right text-slate-600">S/ {{ number_format($settlement->price_per_liter, 2) }}</td>
                        <td class="py-2.5 text-right font-bold text-slate-900">S/ {{ number_format($settlement->gross_total, 2) }}</td>
                    </tr>
                    @forelse($settlement->deductions as $deduction)
                    <tr>
                        <td class="py-2 font-medium text-amber-700">
                            {{ $deduction->concept }}
                            @if(stripos($deduction->concept, 'queso') !== false)
                                <span class="block text-[11px] font-normal text-amber-600">Descuento por Compra de Quesos</span>
                            @elseif(stripos($deduction->concept, 'agua') !== false)
                                <span class="block text-[11px] font-normal text-rose-500">Penalidad por Adulteración de Agua</span>
                            @endif
                        </td>
                        <td class="py-2 text-center text-slate-500">-</td>
                        <td class="py-2 text-right text-slate-500">-</td>
                        <td class="py-2 text-right font-semibold text-rose-700">- S/ {{ number_format($deduction->amount, 2) }}</td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="py-4 border-b-2 border-slate-800">
            <div class="flex items-center justify-between text-base">
                <div>
                    <span class="font-black text-slate-900 uppercase">Total Neto en Sobre</span>
                    <span class="block text-xs text-emerald-700 font-semibold">Monto pagado en mano (Efectivo)</span>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-black text-emerald-800">S/ {{ number_format($settlement->net_total, 2) }}</span>
                    <span class="block text-[10px] text-slate-500 uppercase tracking-wider font-semibold">SOLES EXACTOS</span>
                </div>
            </div>
        </div>


        <!-- Firmas -->
        <div class="pt-8 pb-4 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="h-14 border-b border-dashed border-slate-400 mb-2"></div>
                <p class="font-bold text-slate-800">{{ $settlement->paidBy->name ?? 'Pagador de Campo' }}</p>
                <p class="text-[11px] text-slate-500">Encargado de Pagos en Ruta</p>
            </div>
            <div>
                <div class="h-14 border-b border-dashed border-slate-400 mb-2"></div>
                <p class="font-bold text-slate-800">{{ $settlement->producer->name }}</p>
                <p class="text-[11px] text-slate-500">Firma / Huella del Productor</p>
            </div>
        </div>

        <div class="mt-4 text-center text-[11px] text-slate-400 border-t border-slate-100 pt-3">
            Huata, Puno — Sistema MilkFlow • Pagos en ruta durante el acopio del día viernes
        </div>
    </div>
</div>
@endsection
