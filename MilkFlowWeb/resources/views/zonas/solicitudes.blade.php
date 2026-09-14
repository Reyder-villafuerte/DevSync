@extends('layouts.app')

@section('title', 'Aprobación de Solicitudes de Rotación de Zona')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-arrows-split-up-and-left"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Solicitudes de Cambio de Zona</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">
                        Rotación de Pastoreo
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Revisa y aprueba el traslado de productores entre las 4 zonas de Huata. Al aprobar, el productor se asigna inmediatamente a la nueva ruta de acopio.
                </p>
            </div>
        </div>

        <a href="{{ route('zonas.index') }}" class="px-3.5 py-2 rounded-2xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
            <i class="fa-solid fa-map-location-dot text-emerald-700"></i> Ver Zonas (1 a 4)
        </a>
    </div>

    <!-- TARJETAS DE RESUMEN DE SOLICITUDES -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <!-- Pendientes -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Pendientes de Decisión</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-amber-600">{{ $totalPendientes }}</span>
                <span class="text-xs font-medium text-slate-400">solicitudes</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Requieren aprobación o rechazo del administrador.</p>
        </div>

        <!-- Aprobadas -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Rotaciones Aprobadas</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-emerald-700">{{ $totalAprobadas }}</span>
                <span class="text-xs font-medium text-slate-400">productores reubicados</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Zona actualizada en planilla de acopio.</p>
        </div>

        <!-- Rechazadas -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Solicitudes Denegadas</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-rose-600">{{ $totalRechazadas }}</span>
                <span class="text-xs font-medium text-slate-400">denegadas</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Permanecen en su zona original de origen.</p>
        </div>
    </div>

    <!-- TABLA DE SOLICITUDES CON PESTAÑAS DE FILTRO -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-black text-slate-900">Listado de Solicitudes</h3>
                <p class="text-xs text-slate-500">Filtrar por estado para agilizar la revisión.</p>
            </div>

            <!-- Pestañas de Estado -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('zonas.solicitudes', ['estado' => 'todos']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === 'todos' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    Todas
                </a>
                <a href="{{ route('zonas.solicitudes', ['estado' => 'pendiente']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === 'pendiente' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    Pendientes ({{ $totalPendientes }})
                </a>
                <a href="{{ route('zonas.solicitudes', ['estado' => 'aprobado']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === 'aprobado' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    Aprobadas ({{ $totalAprobadas }})
                </a>
                <a href="{{ route('zonas.solicitudes', ['estado' => 'rechazado']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === 'rechazado' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    Rechazadas ({{ $totalRechazadas }})
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Fecha</th>
                        <th class="pb-3 px-3">Productor Solicitante</th>
                        <th class="pb-3 px-3">Zona Actual</th>
                        <th class="pb-3 px-3">Zona Destino Solicitada</th>
                        <th class="pb-3 px-3">Motivo del Traslado</th>
                        <th class="pb-3 px-3">Estado</th>
                        <th class="pb-3 px-3 text-right">Acción / Decisión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($solicitudes as $req)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 text-slate-500 font-medium whitespace-nowrap">
                            {{ $req->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="font-bold text-slate-900 block text-xs">{{ $req->producer->name }}</span>
                            <span class="text-[10px] text-slate-400">DNI: {{ $req->producer->dni ?: '—' }} • Cel: {{ $req->producer->phone ?: '—' }}</span>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700 whitespace-nowrap">
                                {{ $req->currentZone->name }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60 whitespace-nowrap">
                                {{ $req->requestedZone->name }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-slate-600 max-w-xs leading-snug">
                            {{ $req->reason ?: 'Sin detalle' }}
                        </td>
                        <td class="py-3.5 px-3">
                            @if($req->status === 'aprobado')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aprobado
                                </span>
                            @elseif($req->status === 'rechazado')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">
                                    Rechazado
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-right whitespace-nowrap">
                            @if($req->status === 'pendiente')
                                <form action="{{ route('zonas.review-request', $req->id) }}" method="POST" class="inline-flex items-center gap-2">
                                    @csrf
                                    <button type="submit" name="decision" value="aprobado" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#0f1713] hover:bg-slate-900 text-[#bef264] transition shadow-sm inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i> Aprobar
                                    </button>
                                    <button type="submit" name="decision" value="rechazado" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 transition inline-flex items-center gap-1">
                                        <i class="fa-solid fa-xmark text-[10px]"></i> Rechazar
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] text-slate-400">
                                    Revisado por {{ $req->reviewer->name ?? 'Admin' }}
                                    @if($req->reviewed_at)
                                        <span class="block text-[9px]">{{ \Carbon\Carbon::parse($req->reviewed_at)->format('d/m/Y') }}</span>
                                    @endif
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 font-medium">No se encontraron solicitudes con el filtro seleccionado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100">
            {{ $solicitudes->links() }}
        </div>
    </div>
</div>
@endsection
