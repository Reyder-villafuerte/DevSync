@extends('layouts.app')

@section('title', 'Zonas de Huata y Solicitudes')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Zonas de Acopio - Distrito de Huata</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">4 Sectores</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Distribución territorial de productores y gestión de solicitudes de cambio de zona por rotación de pastoreo.</p>
            </div>
        </div>
    </div>

    <!-- Zonas 1 a 4 Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($zones as $zone)
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between hover:shadow-md transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-[#0f1713] text-[#bef264]">{{ $zone->code }}</span>
                    <div class="w-7 h-7 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 text-xs">
                        <i class="fa-solid fa-mountain-sun"></i>
                    </div>
                </div>
                <h3 class="text-base font-bold text-slate-900">{{ $zone->name }}</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $zone->description }}</p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400 font-medium">Productores</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800">
                    {{ $zone->producers->count() }} registrados
                </span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Solicitudes de cambio de zona pendientes (Para Admin) -->
    @if(in_array(Auth::user()->role, ['admin', 'jefe_general']))
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-arrows-split-up-and-left"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Solicitudes de Rotación de Zona Pendientes</h3>
            </div>
            <span class="text-xs text-slate-400 font-medium">Pendientes: {{ $pendingRequests->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Fecha</th>
                        <th class="pb-3 px-3">Productor</th>
                        <th class="pb-3 px-3">Zona Actual</th>
                        <th class="pb-3 px-3">Zona Solicitada</th>
                        <th class="pb-3 px-3">Motivo</th>
                        <th class="pb-3 px-3 text-right">Decisión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($pendingRequests as $req)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 text-slate-500 font-medium">{{ $req->created_at->format('Y-m-d') }}</td>
                        <td class="py-3.5 px-3 font-bold text-slate-800">{{ $req->producer->name }}</td>
                        <td class="py-3.5 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                {{ $req->currentZone->name }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60">
                                {{ $req->requestedZone->name }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-slate-600 max-w-xs truncate">{{ $req->reason ?: 'Sin detalle' }}</td>
                        <td class="py-3.5 px-3 text-right">
                            <form action="{{ route('zonas.review-request', $req->id) }}" method="POST" class="inline-flex items-center gap-2">
                                @csrf
                                <button type="submit" name="decision" value="aprobado" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#0f1713] hover:bg-slate-900 text-[#bef264] transition">
                                    Aprobar
                                </button>
                                <button type="submit" name="decision" value="rechazado" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 transition">
                                    Rechazar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 font-medium">No hay solicitudes de cambio de zona pendientes en este momento.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
