@extends('layouts.app')

@section('title', 'Zonas de Huata y Solicitudes')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Zonas de Acopio · Distrito de Huata"
        descripcion="Los cuatro sectores en que se reparte el distrito y cuántos productores tiene cada uno. Son fijos: la rotación se maneja asignando el acopiador del día."
        :coleccion="$zones"
        :columnas="4"
        vacio="No hay zonas configuradas.">

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Código</th>
            <th class="text-left py-3 px-4 font-bold">Zona</th>
            <th class="text-left py-3 px-4 font-bold">Sectores que cubre</th>
            <th class="text-right py-3 px-4 font-bold">Productores</th>
        </x-slot:encabezados>

        @foreach($zones as $zone)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $zone->id }}</td>
            <td class="py-3 px-4">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-[#0f1713] text-[#bef264]">{{ $zone->code }}</span>
            </td>
            <td class="py-3 px-4 font-bold text-slate-900">{{ $zone->name }}</td>
            <td class="py-3 px-4 text-slate-500">{{ $zone->description }}</td>
            <td class="py-3 px-4 text-right font-black text-slate-800">{{ $zone->producers->count() }}</td>
        </tr>
        @endforeach
    </x-tabla>

    @if(in_array(Auth::user()->role, ['admin', 'jefe_general']))
    <x-tabla
        titulo="Asignación de zonas de hoy"
        descripcion="Qué acopiador cubre cada zona el {{ $today }}. Son 4 zonas y 5 acopiadores: el que queda fuera descansa."
        :coleccion="$zones"
        :columnas="5"
        vacio="No hay zonas configuradas.">

        <x-slot:acciones>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 font-medium whitespace-nowrap">Asignadas: {{ $rutasDeHoy->count() }} de {{ $zones->count() }}</span>
                @if(Auth::user()->role === 'admin')
                <button type="button" data-abrir="modalAsignarZona"
                    class="px-4 py-2.5 rounded-xl bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold text-[11px] uppercase tracking-wider whitespace-nowrap">
                    <i class="fa-solid fa-calendar-check mr-1"></i> Asignar zona
                </button>
                @endif
            </div>
        </x-slot:acciones>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Zona</th>
            <th class="text-left py-3 px-4 font-bold">Acopiador asignado</th>
            <th class="text-left py-3 px-4 font-bold">Hora de salida</th>
            <th class="text-right py-3 px-4 font-bold">Litros anotados</th>
            <th class="text-right py-3 px-4 font-bold">Estado</th>
        </x-slot:encabezados>

        @foreach($zones as $zone)
        @php($ruta = $rutasDeHoy->firstWhere('zone_id', $zone->id))
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $ruta?->id ?? '—' }}</td>
            <td class="py-3 px-4 font-bold text-slate-900">{{ $zone->name }}</td>
            <td class="py-3 px-4 text-slate-700 font-medium">{{ $ruta?->collector?->name ?? '—' }}</td>
            <td class="py-3 px-4 font-mono text-slate-500 font-bold">{{ $ruta?->start_time ?? '—' }}</td>
            <td class="py-3 px-4 text-right font-extrabold text-[#0f1713]">
                @if($ruta)
                {{ number_format($ruta->total_collected_liters, 2) }} L
                @else
                <span class="font-medium text-slate-300">—</span>
                @endif
            </td>
            <td class="py-3 px-4 text-right">
                @if($ruta)
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                    {{ $ruta->status === 'descargado' ? 'bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ $ruta->status }}
                </span>
                @else
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500">
                    Sin asignar
                </span>
                @endif
            </td>
        </tr>
        @endforeach
    </x-tabla>
    @endif

</div>

@if(Auth::user()->role === 'admin')
{{-- Modal: asignar un acopiador a una zona --}}
<div id="modalAsignarZona" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Asignar acopiador a zona</h3>
                <p class="text-[11px] text-slate-500">La salida del camión es a las 4:30 AM.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form action="{{ route('acopio.assign') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Fecha de salida</label>
                <input type="date" name="date" value="{{ old('date', $today) }}" required
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:bg-white">
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
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Hora de salida</label>
                <input type="time" name="start_time" value="{{ old('start_time', '04:30') }}" required
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:bg-white">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar asignación</button>
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

    // Si la asignación fue rechazada, el formulario vuelve con errores:
    // que el modal siga abierto en vez de esconder el motivo.
    @if($errors->any())
    modal.hidden = false;
    @endif
})();
</script>
@endif
@endsection
