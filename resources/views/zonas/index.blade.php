@extends('layouts.app')

@section('title', 'Zonas de Huata y Solicitudes')

@section('content')
@php
    $puedeVerAsignacion = in_array(Auth::user()->role, ['admin', 'jefe_general']);
    $esAdmin = Auth::user()->role === 'admin';
    $rutasDeHoy = $rutasDeHoy ?? collect();
    $collectors = $collectors ?? collect();
    $totalProductores = $zones->sum(fn ($z) => $z->producers->count());
    $totalLitros = $rutasDeHoy->sum('total_collected_liters');
    $descansan = $collectors->whereNotIn('id', $rutasDeHoy->pluck('collector_id'));
@endphp

<div class="space-y-6">

    {{-- ===== Encabezado ===== --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <p class="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Distrito de Huata</p>
            <h1 class="text-2xl font-black text-slate-900 mt-1">Zonas de acopio</h1>
            <p class="text-xs text-slate-500 mt-1 max-w-xl">
                Los cuatro sectores del distrito son fijos; la rotación se maneja asignando el acopiador del día.
            </p>
        </div>

        @if($esAdmin)
        <button type="button" data-abrir="modalAsignarZona"
            class="self-start lg:self-auto px-5 py-3 rounded-2xl bg-[#E65100] hover:bg-[#A33C00] text-white font-bold text-[11px] uppercase tracking-wider whitespace-nowrap shadow-sm transition">
            <i class="fa-solid fa-calendar-check mr-1.5"></i> Asignar zona
        </button>
        @endif
    </div>

    {{-- ===== Resumen ===== --}}
    <div class="grid grid-cols-2 {{ $puedeVerAsignacion ? 'lg:grid-cols-4' : 'lg:grid-cols-2' }} gap-4">
        <div class="bg-white rounded-2xl border border-slate-100 p-4">
            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Zonas</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ $zones->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4">
            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Productores</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalProductores }}</p>
        </div>
        @if($puedeVerAsignacion)
        <div class="bg-white rounded-2xl border border-slate-100 p-4">
            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Asignadas hoy</p>
            <p class="text-2xl font-black text-slate-900 mt-1">
                {{ $rutasDeHoy->count() }}<span class="text-sm text-slate-400 font-bold"> / {{ $zones->count() }}</span>
            </p>
        </div>
        <div class="bg-[#1565C0] rounded-2xl p-4">
            <p class="text-[10px] uppercase font-bold tracking-wider text-white/70">Litros de hoy</p>
            <p class="text-2xl font-black text-white mt-1">{{ number_format($totalLitros, 2) }} L</p>
        </div>
        @endif
    </div>

    {{-- ===== Tarjetas por zona ===== --}}
    @if($zones->isEmpty())
        <div class="bg-white rounded-3xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-400">
            No hay zonas configuradas.
        </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($zones as $zone)
        @php($ruta = $rutasDeHoy->firstWhere('zone_id', $zone->id))
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition flex flex-col overflow-hidden">

            {{-- Datos fijos de la zona --}}
            <div class="p-5 flex-1">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-[#1565C0] text-white">
                        {{ $zone->code }}
                    </span>
                    <span class="text-[10px] font-mono text-slate-300">#{{ $zone->id }}</span>
                </div>

                <h3 class="text-base font-black text-slate-900 mt-3">{{ $zone->name }}</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $zone->description }}</p>

                <div class="mt-4 flex items-center gap-2 text-xs text-slate-600">
                    <i class="fa-solid fa-users text-slate-300"></i>
                    <span><strong class="text-slate-900 font-black">{{ $zone->producers->count() }}</strong> productores</span>
                </div>
            </div>

            {{-- Asignación de hoy --}}
            @if($puedeVerAsignacion)
            <div class="border-t border-slate-100 bg-slate-50/70 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Hoy · {{ $today }}</p>
                    @if($ruta)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                            {{ $ruta->status === 'descargado'
                                ? 'bg-[#1565C0]/10 text-[#1565C0] border border-[#1565C0]/20'
                                : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                            {{ $ruta->status }}
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500">
                            Sin asignar
                        </span>
                    @endif
                </div>

                @if($ruta)
                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-400">Acopiador</dt>
                        <dd class="font-bold text-slate-900 text-right">{{ $ruta->collector?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-400">Salida</dt>
                        <dd class="font-mono font-bold text-slate-700">{{ $ruta->start_time ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-400">Litros</dt>
                        <dd class="font-extrabold text-[#1565C0]">{{ number_format($ruta->total_collected_liters, 2) }} L</dd>
                    </div>
                </dl>
                @else
                <p class="text-xs text-slate-400">Ningún acopiador cubre esta zona hoy.</p>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== Quién descansa ===== --}}
    @if($puedeVerAsignacion && $collectors->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-wrap items-center gap-2">
        <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 mr-1">
            <i class="fa-solid fa-mug-hot mr-1"></i> Descansa hoy:
        </span>
        @forelse($descansan as $c)
            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">{{ $c->name }}</span>
        @empty
            <span class="text-xs text-slate-400">Nadie, todos tienen ruta.</span>
        @endforelse
    </div>
    @endif

</div>

@if($esAdmin)
{{-- Modal: asignar un acopiador a una zona --}}
<div id="modalAsignarZona" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[rgba(32,30,29,0.55)]">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Asignar acopiador a zona</h3>
                <p class="text-[11px] text-slate-500">La salida del camión es a las 4:30 AM.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        @if($errors->any())
        <div class="mb-3 p-3 rounded-xl bg-[#FFEBEE] border border-[#C62828]/25 text-[11px] text-[#C62828]">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form action="{{ route('acopio.assign') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Fecha de salida</label>
                    <input type="date" name="date" value="{{ old('date', $today) }}" required
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:bg-white">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Hora de salida</label>
                    <input type="time" name="start_time" value="{{ old('start_time', '04:30') }}" required
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:bg-white">
                </div>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Zona de Huata</label>
                <select name="zone_id" required
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:bg-white">
                    @foreach($zones as $zone)
                    <option value="{{ $zone->id }}" @selected(old('zone_id') == $zone->id)>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Acopiador responsable</label>
                <select name="collector_id" required
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:bg-white">
                    @foreach($collectors as $collector)
                    <option value="{{ $collector->id }}" @selected(old('collector_id') == $collector->id)>
                        {{ $collector->name }}{{ $collector->phone ? ' ('.$collector->phone.')' : '' }}
                    </option>
                    @endforeach
                </select>
                <span class="text-[10px] text-slate-400 mt-1 block">
                    Un acopiador solo puede tener una ruta por día.
                </span>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-[#E65100] hover:bg-[#A33C00] text-white font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar asignación</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalAsignarZona');

    document.querySelectorAll('[data-abrir="modalAsignarZona"]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = false; });
    });

    modal.querySelectorAll('[data-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = true; });
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) { modal.hidden = true; }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') { modal.hidden = true; }
    });

    // Si la asignación fue rechazada, el modal vuelve abierto mostrando el motivo.
    @if($errors->any())
    modal.hidden = false;
    @endif
})();
</script>
@endif
@endsection
