@extends('layouts.app')

@section('title', 'Asignación de Rutas de Acopio')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-truck-ramp-box"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Panel de Asignación de Rutas (Salida 4:30 AM)</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Rutas Diarias</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Asigna qué acopiador cubre cada una de las 4 zonas de Huata para hoy ({{ $today }}) considerando rotación de los 5 acopiadores.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Formulario de asignación -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-route"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Asignar Acopiador a Zona</h3>
            </div>

            <form action="{{ route('acopio.assign') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha de Salida</label>
                    <input type="date" name="date" value="{{ $today }}" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Zona de Huata</label>
                    <select name="zone_id" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Acopiador Responsable (Camión)</label>
                    <select name="collector_id" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                        @foreach($collectors as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hora de Salida Programada</label>
                    <input type="time" name="start_time" value="04:30" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                </div>

                <button type="submit" class="w-full bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold py-3 rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-calendar-check"></i> Guardar Asignación
                </button>
            </form>
        </div>

        <!-- Tabla de rutas asignadas hoy -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-map-pin"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Rutas Programadas para Hoy</h3>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Asignadas: {{ count($routes) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="pb-3 px-3">Zona</th>
                                <th class="pb-3 px-3">Acopiador Asignado</th>
                                <th class="pb-3 px-3">Hora Salida</th>
                                <th class="pb-3 px-3">Litros Anotados</th>
                                <th class="pb-3 px-3 text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($routes as $r)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-3 font-bold text-slate-900">{{ $r->zone->name }}</td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium">{{ $r->collector->name }}</td>
                                <td class="py-3.5 px-3 font-mono text-slate-500 font-bold">{{ $r->start_time }}</td>
                                <td class="py-3.5 px-3 font-extrabold text-[#0f1713]">{{ number_format($r->total_collected_liters, 2) }} L</td>
                                <td class="py-3.5 px-3 text-right">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                                        {{ $r->status === 'descargado' ? 'bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ $r->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400 font-medium">No se han configurado rutas para hoy todavía.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
