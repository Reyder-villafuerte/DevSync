@extends('layouts.app')

@section('title', 'Historial de Pagos y Liquidaciones - Proveedor')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Historial de Pagos y Liquidaciones Semanales</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Cobros</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Resumen de semanas liquidadas. Al cerrarse una semana y cambiar a estado Pagado, el contador semanal en Acopio se reinicia para el siguiente ciclo.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('productor.acopio') }}" class="px-3.5 py-2 rounded-2xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Volver a Acopio
            </a>
            <!-- Botón para cerrar y pagar la semana activa (prueba/operativa) -->
            <form action="{{ route('productor.liquidar', $user->id) }}" method="POST" onsubmit="return confirm('¿Deseas cerrar y liquidar la semana actual de este productor? El estado cambiará a Pagado y el acumulador semanal se reiniciará a cero para el nuevo ciclo.')">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-2xl bg-[#0f1713] text-[#bef264] text-xs font-bold hover:bg-slate-900 transition inline-flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-check-circle"></i> Liquidar y Cerrar Semana
                </button>
            </form>
        </div>
    </div>

    <!-- TARJETAS DE RESUMEN DE COBROS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Total Pagado Histórico -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Cobrado Histórico</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-emerald-700">S/ {{ number_format($totalPagadoHistorico, 2) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Monto neto efectivamente pagado en efectivo.</p>
        </div>

        <!-- Pendiente de Cobro -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Liquidaciones Pendientes</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-amber-600">S/ {{ number_format($totalPendienteCobro, 2) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">En proceso de conciliación con caudalímetro.</p>
        </div>

        <!-- Ciclo y Frecuencia -->
        <div class="bg-[#0f1713] text-white rounded-3xl p-6 shadow-md flex flex-col justify-between">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264] text-[#0f1713] uppercase tracking-wider">Ciclo Semanal</span>
                <p class="text-xs text-slate-300 mt-3 leading-relaxed">
                    Las semanas se liquidan todos los lunes. El pago es inmediato en caja en efectivo en la planta de acopio.
                </p>
            </div>
            <span class="text-[11px] text-[#bef264] font-medium mt-3">Tarifa oficial Huata: S/ 1.40 / L</span>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL DE LIQUIDACIONES -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Liquidaciones Registradas</h3>
            </div>
            <span class="text-xs text-slate-400 font-medium">Total: {{ $liquidaciones->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Código Liquidación</th>
                        <th class="pb-3 px-3">Período de Acopio</th>
                        <th class="pb-3 px-3">Litros Acopiados</th>
                        <th class="pb-3 px-3">Subtotal Bruto</th>
                        <th class="pb-3 px-3">Descuentos</th>
                        <th class="pb-3 px-3">Neto Cobrado</th>
                        <th class="pb-3 px-3">Estado</th>
                        <th class="pb-3 px-3 text-right">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($liquidaciones as $liq)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 font-mono font-bold text-slate-900">{{ $liq->settlement_code }}</td>
                        <td class="py-3.5 px-3 font-medium text-slate-700">
                            {{ $liq->start_date->format('d/m/Y') }} al {{ $liq->end_date->format('d/m/Y') }}
                        </td>
                        <td class="py-3.5 px-3 font-bold text-slate-800">{{ number_format($liq->total_liters, 2) }} L</td>
                        <td class="py-3.5 px-3 text-slate-600">S/ {{ number_format($liq->gross_total, 2) }}</td>
                        <td class="py-3.5 px-3 text-rose-600 font-semibold">
                            {{ $liq->deductions_total > 0 ? '- S/ ' . number_format($liq->deductions_total, 2) : 'S/ 0.00' }}
                        </td>
                        <td class="py-3.5 px-3 font-black text-slate-900 text-sm">
                            S/ {{ number_format($liq->net_total, 2) }}
                        </td>
                        <td class="py-3.5 px-3">
                            @if($liq->status === 'pagado')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60">
                                    <i class="fa-solid fa-check text-[9px] mr-1"></i> Pagado
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            <a href="{{ route('productor.pagos.recibo', $liq->id) }}" target="_blank" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs inline-flex items-center gap-1.5 transition">
                                <i class="fa-solid fa-print text-slate-500"></i> Recibo
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400 font-medium">No se registran liquidaciones pasadas cerradas aún.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
