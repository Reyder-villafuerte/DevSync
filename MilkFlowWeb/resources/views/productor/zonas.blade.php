@extends('layouts.app')

@section('title', 'Cambio de Zona y Rotación - Proveedor')

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
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Cambio de Zona de Acopio</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Rotación de Pastoreo</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Si trasladas tu ganado o rotas a otro sector de Huata, solicita la actualización para que el camión acopiador pase por tu nueva ubicación.
                </p>
            </div>
        </div>

        <a href="{{ route('productor.acopio') }}" class="px-3.5 py-2 rounded-2xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver a Acopio
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- FORMULARIO DE SOLICITUD DE ROTACIÓN -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Nueva Solicitud de Rotación</h3>
            </div>

            <!-- Zona actual del productor -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/60">
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Tu Zona Actual:</span>
                <span class="text-sm font-extrabold text-[#0f1713] mt-0.5 block">
                    {{ $miZona ? $miZona->name : 'Sin Zona Registrada' }}
                </span>
                @if($miZona)
                <p class="text-[11px] text-slate-500 mt-1 leading-snug">{{ $miZona->description }}</p>
                @endif
            </div>

            <form action="{{ route('productor.zonas.solicitar') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Zona Destino Solicitada
                    </label>
                    <select name="requested_zone_id" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition font-medium text-slate-800">
                        @foreach($zonasDisponibles as $z)
                            @if(!$miZona || $z->id !== $miZona->id)
                                <option value="{{ $z->id }}">{{ $z->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Motivo del Traslado o Rotación
                    </label>
                    <textarea name="reason" rows="4" required placeholder="Ej. Traslado temporal de ganado por temporada de pastos secos al sector Joche / Faón..." class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition"></textarea>
                </div>

                <button type="submit" class="w-full bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold py-3 rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-route"></i> Enviar Solicitud de Rotación
                </button>
            </form>
        </div>

        <!-- HISTORIAL DE SOLICITUDES DE CAMBIO DE ZONA -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Historial de Solicitudes de Cambio</h3>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Total: {{ $solicitudes->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="pb-3 px-3">Fecha</th>
                                <th class="pb-3 px-3">Zona Origen</th>
                                <th class="pb-3 px-3">Zona Solicitada</th>
                                <th class="pb-3 px-3">Motivo</th>
                                <th class="pb-3 px-3 text-right">Estado Decisión</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($solicitudes as $sol)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-3 font-medium text-slate-600">{{ $sol->created_at->format('d/m/Y') }}</td>
                                <td class="py-3.5 px-3 font-bold text-slate-800">{{ $sol->currentZone->name }}</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50">
                                        {{ $sol->requestedZone->name }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 max-w-xs truncate">{{ $sol->reason }}</td>
                                <td class="py-3.5 px-3 text-right">
                                    @if($sol->status === 'aprobado')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aprobado
                                        </span>
                                    @elseif($sol->status === 'rechazado')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">
                                            Rechazado
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                            En Revisión
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400 font-medium">No has registrado solicitudes de rotación de zona aún.</td>
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
