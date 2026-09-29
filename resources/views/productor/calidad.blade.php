@extends('layouts.app')

@section('title', 'Control de Calidad de Leche - Proveedor')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-vial-circle-check"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Reporte de Calidad e Inspección de Leche</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Analizador Lactoscan</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Resultados de las pruebas de laboratorio tomadas en ruta o planta. Si se detecta acidez alta, se programa visita de asistencia técnica en tu establo.
                </p>
            </div>
        </div>

        <a href="{{ route('productor.acopio') }}" class="px-3.5 py-2 rounded-2xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver a Acopio
        </a>
    </div>

    <!-- ALERTA DE VISITAS TÉCNICAS PROGRAMADAS (SI APLICA) -->
    @if($visitasPendientes->count() > 0)
    <div class="bg-amber-500/10 border-2 border-amber-500/30 rounded-3xl p-6 text-slate-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-sm flex-shrink-0">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-200 text-amber-900 tracking-wider">Visita Técnica en tu Establo</span>
                <h3 class="text-base font-black mt-1 text-amber-950">Asistencia Técnica Programada</h3>
                @foreach($visitasPendientes as $vp)
                <p class="text-xs text-amber-900/80 mt-0.5">
                    Fecha: <strong>{{ \Carbon\Carbon::parse($vp->scheduled_date)->format('d/m/Y') }}</strong> • Inspector: <strong>{{ $vp->inspector->name ?? 'Equipo Técnico' }}</strong> • Motivo: <em>{{ $vp->reason }}</em>
                </p>
                @endforeach
            </div>
        </div>
        <div class="px-3 py-1.5 rounded-xl bg-amber-200 text-amber-900 text-xs font-bold whitespace-nowrap">
            Confirmada
        </div>
    </div>
    @endif

    <!-- TARJETAS DE PROMEDIOS FÍSICO-QUÍMICOS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <!-- Grasa Promedio -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Grasa Promedio</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-[#0f1713]">{{ number_format($promedioGrasa, 2) }}%</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Estándar mínimo óptimo: ≥ 3.2%</p>
        </div>

        <!-- Densidad Promedio -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Densidad Promedio</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-[#0f1713]">{{ number_format($promedioDensidad, 3) }}</span>
                <span class="text-xs text-slate-400">g/cm³</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Rango normal: 1.028 - 1.033 g/cm³</p>
        </div>

        <!-- Acidez Promedio -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Acidez Promedio</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black {{ $promedioAcidez > 18 ? 'text-rose-600' : 'text-emerald-700' }}">{{ number_format($promedioAcidez, 1) }}°D</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Rango óptimo para queso: 14°D - 18°D</p>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL DE INSPECCIONES LACTOSCAN -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-flask"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Historial de Pruebas Lactoscan</h3>
            </div>
            <span class="text-xs text-slate-400 font-medium">Evaluaciones: {{ $analisis->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Fecha</th>
                        <th class="pb-3 px-3">Inspector</th>
                        <th class="pb-3 px-3">Grasa (%)</th>
                        <th class="pb-3 px-3">Densidad</th>
                        <th class="pb-3 px-3">Sólidos No Grasos</th>
                        <th class="pb-3 px-3">Proteína</th>
                        <th class="pb-3 px-3">Agua Añadida</th>
                        <th class="pb-3 px-3">Acidez (°D)</th>
                        <th class="pb-3 px-3 text-right">Veredicto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($analisis as $ana)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 font-bold text-slate-900">{{ \Carbon\Carbon::parse($ana->analysis_date)->format('d/m/Y') }}</td>
                        <td class="py-3.5 px-3 text-slate-600">{{ $ana->inspector->name ?? 'Inspector' }}</td>
                        <td class="py-3.5 px-3 font-semibold">{{ number_format($ana->fat_percentage, 2) }}%</td>
                        <td class="py-3.5 px-3">{{ number_format($ana->density, 3) }}</td>
                        <td class="py-3.5 px-3 text-slate-600">{{ number_format($ana->snf_percentage, 2) }}%</td>
                        <td class="py-3.5 px-3 text-slate-600">{{ number_format($ana->protein_percentage, 2) }}%</td>
                        <td class="py-3.5 px-3">
                            @if($ana->water_addition_percentage > 0)
                                <span class="font-bold text-rose-600">{{ number_format($ana->water_addition_percentage, 1) }}%</span>
                            @else
                                <span class="text-slate-400">0.0%</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 font-bold {{ $ana->ph_or_acidity > 18 ? 'text-rose-600' : 'text-slate-800' }}">
                            {{ number_format($ana->ph_or_acidity, 1) }}°D
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            @if($ana->verdict === 'conforme')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60">
                                    Conforme
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">
                                    {{ str_replace('_', ' ', $ana->verdict) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center text-slate-400 font-medium">No se registran análisis de calidad para tu establo aún.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100">
            {{ $analisis->links() }}
        </div>
    </div>
</div>
@endsection
