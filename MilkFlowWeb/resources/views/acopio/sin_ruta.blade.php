@extends('layouts.app')

@section('title', 'Turno de Rotación - Acopio Huata')

@section('content')
<div class="max-w-2xl mx-auto py-10 space-y-6">
    <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-[0_4px_20px_rgba(0,0,0,0.03)] text-center space-y-5">
        <div class="w-16 h-16 rounded-3xl bg-amber-50 text-amber-600 border border-amber-200/60 flex items-center justify-center text-2xl mx-auto shadow-sm">
            <i class="fa-solid fa-calendar-check"></i>
        </div>

        <div>
            <span class="text-[10px] bg-amber-100 text-amber-900 font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">
                Turno de Descanso / Rotación
            </span>
            <h1 class="text-xl font-black text-slate-900 tracking-tight mt-3">
                No tienes ruta de campo asignada para hoy
            </h1>
            <p class="text-xs text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                Las 4 zonas distritales de Huata ya cuentan con sus rutas asignadas para hoy <strong>{{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}</strong>. Conforme al rol rotativo de acopiadores, te encuentras en día de descanso o relevo operativo en planta.
            </p>
        </div>

        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 max-w-sm mx-auto text-left text-xs space-y-2">
            <div class="flex items-center gap-2 font-bold text-slate-700">
                <i class="fa-solid fa-circle-info text-sky-500"></i>
                <span>Zonas activas del Distrito de Huata:</span>
            </div>
            <ul class="text-[11px] text-slate-500 space-y-1 pl-5 list-disc">
                <li>Zona 1: Zona Urbana Central</li>
                <li>Zona 2: Yocará / Joche</li>
                <li>Zona 3: Sucasco / Cochiraya</li>
                <li>Zona 4: Faón / Collana</li>
            </ul>
        </div>

        <div class="pt-2 flex justify-center gap-3">
            <a href="{{ route('acopio.index') }}" class="px-5 py-2.5 rounded-2xl bg-[#0f1713] text-[#bef264] text-xs font-bold hover:bg-black transition inline-flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-arrows-rotate"></i> Actualizar Planilla
            </a>
        </div>
    </div>

    @if(isset($historicalRoutes) && $historicalRoutes->count() > 0)
    <!-- Historial de Rutas Previas en Descanso -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-500"></i>
                Historial de Rutas y Observaciones de Planta
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Rutas anteriores acopiadas con caudales medidos y observaciones de planta.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3 rounded-l-xl">Fecha</th>
                        <th class="p-3">Zona</th>
                        <th class="p-3">Campo</th>
                        <th class="p-3">Caudalímetro</th>
                        <th class="p-3">Diferencia</th>
                        <th class="p-3">Observación Planta</th>
                        <th class="p-3 rounded-r-xl">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($historicalRoutes as $histRoute)
                        @php
                            $rec = $histRoute->reception;
                            $diff = $rec && $rec->flowmeter_liters !== null ? ($rec->flowmeter_liters - $histRoute->total_collected_liters) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 font-mono font-bold text-slate-700">{{ \Carbon\Carbon::parse($histRoute->date)->format('d/m/Y') }}</td>
                            <td class="p-3 font-bold text-slate-800">{{ $histRoute->zone ? $histRoute->zone->name : 'Zona' }}</td>
                            <td class="p-3 font-black text-slate-900">{{ number_format($histRoute->total_collected_liters, 2) }} L</td>
                            <td class="p-3 font-black">{{ $rec && $rec->flowmeter_liters !== null ? number_format($rec->flowmeter_liters, 2) . ' L' : 'Pendiente' }}</td>
                            <td class="p-3 font-bold">
                                @if($diff !== null)
                                    @if($diff < -0.01)
                                        <span class="text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full text-[10px]">{{ number_format($diff, 2) }} L</span>
                                    @elseif($diff > 0.01)
                                        <span class="text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-full text-[10px]">+{{ number_format($diff, 2) }} L</span>
                                    @else
                                        <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full text-[10px]">0.00 L</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-700 italic max-w-xs truncate">
                                {{ $rec && $rec->observation ? $rec->observation : ($rec ? 'Todo conforme' : 'En espera') }}
                            </td>
                            <td class="p-3">
                                @if($histRoute->status === 'verificada')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Verificada</span>
                                @elseif($histRoute->status === 'descargada_planta')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900">En Planta</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-lime-100 text-slate-900">En Ruta</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
