@extends('layouts.app')

@section('title', 'Aprobación de Solicitudes de Rotación de Zona')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Solicitudes de cambio de zona"
        descripcion="El traslado de productores entre las 4 zonas de Huata. Al aprobar, el productor queda en la nueva ruta de acopio desde el día siguiente."
        :coleccion="$solicitudes"
        :columnas="7"
        vacio="No se encontraron solicitudes con el filtro seleccionado.">

        <x-slot:acciones>
            <a href="{{ route('zonas.index') }}"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-[11px] font-bold uppercase tracking-wider hover:bg-slate-50 transition whitespace-nowrap">
                <i class="fa-solid fa-map-location-dot mr-1 text-emerald-700"></i> Ver zonas
            </a>
        </x-slot:acciones>

        <x-slot:filtros>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <form method="GET" action="{{ route('zonas.solicitudes') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="estado" value="{{ $status }}">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Productor o DNI..."
                            class="w-56 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-[#bef264]">
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-[#bef264] font-bold text-[10px] uppercase">Buscar</button>
                    @if(request('buscar'))
                    <a href="{{ route('zonas.solicitudes', ['estado' => $status]) }}" class="text-[11px] font-bold text-slate-500 underline">Quitar búsqueda</a>
                    @endif
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $pestanas = [
                            'todos' => 'Todas',
                            'pendiente' => 'Pendientes ('.$totalPendientes.')',
                            'aprobado' => 'Aprobadas ('.$totalAprobadas.')',
                            'rechazado' => 'Rechazadas ('.$totalRechazadas.')',
                        ];
                    @endphp
                    @foreach($pestanas as $clave => $etiqueta)
                    <a href="{{ route('zonas.solicitudes', ['estado' => $clave, 'buscar' => request('buscar')]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === $clave ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-white border border-slate-200 hover:bg-slate-100 text-slate-700' }}">
                        {{ $etiqueta }}
                    </a>
                    @endforeach
                </div>
            </div>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Fecha</th>
            <th class="text-left py-3 px-4 font-bold">Productor</th>
            <th class="text-left py-3 px-4 font-bold">Zona actual</th>
            <th class="text-left py-3 px-4 font-bold">Zona solicitada</th>
            <th class="text-left py-3 px-4 font-bold">Motivo</th>
            <th class="text-left py-3 px-4 font-bold">Estado</th>
            <th class="text-right py-3 px-4 font-bold">Decisión</th>
        </x-slot:encabezados>

        @foreach($solicitudes as $req)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition align-top">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $req->id }}</td>
            <td class="py-3 px-4 text-slate-500 font-medium whitespace-nowrap">
                {{ $req->created_at->format('d/m/Y H:i') }}
            </td>
            <td class="py-3 px-4">
                <span class="font-bold text-slate-900 block">{{ $req->producer->name }}</span>
                <span class="text-[10px] text-slate-400">DNI: {{ $req->producer->dni ?: '—' }} • Cel: {{ $req->producer->phone ?: '—' }}</span>
            </td>
            <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700 whitespace-nowrap">
                    {{ $req->currentZone->name }}
                </span>
            </td>
            <td class="py-3 px-4">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60 whitespace-nowrap">
                    {{ $req->requestedZone->name }}
                </span>
            </td>
            <td class="py-3 px-4 text-slate-600 max-w-xs leading-snug">
                {{ $req->reason ?: 'Sin detalle' }}
            </td>
            <td class="py-3 px-4">
                @if($req->status === 'aprobado')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">Aprobado</span>
                @elseif($req->status === 'rechazado')
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">Rechazado</span>
                @else
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">Pendiente</span>
                @endif
            </td>
            <td class="py-3 px-4 text-right whitespace-nowrap">
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
        @endforeach
    </x-tabla>

</div>
@endsection
