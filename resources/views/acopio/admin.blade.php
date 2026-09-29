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

    <x-tabla
        titulo="Rutas programadas para hoy"
        descripcion="Qué acopiador sale a cada zona, a qué hora y cuántos litros lleva anotados. Son 4 zonas: el que queda fuera descansa."
        :coleccion="$routes"
        :columnas="5"
        vacio="No se han configurado rutas para hoy todavía.">

        <x-slot:acciones>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 font-medium whitespace-nowrap">Asignadas: {{ count($routes) }}</span>
                <button type="button" data-asignar-ruta
                    class="px-4 py-2.5 rounded-xl bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                    <i class="fa-solid fa-plus mr-1"></i> Agregar
                </button>
            </div>
        </x-slot:acciones>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Zona</th>
            <th class="text-left py-3 px-4 font-bold">Acopiador asignado</th>
            <th class="text-left py-3 px-4 font-bold">Hora de salida</th>
            <th class="text-right py-3 px-4 font-bold">Litros anotados</th>
            <th class="text-right py-3 px-4 font-bold">Estado</th>
        </x-slot:encabezados>

        @foreach($routes as $r)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $r->id }}</td>
            <td class="py-3 px-4 font-bold text-slate-900">{{ $r->zone->name }}</td>
            <td class="py-3 px-4 text-slate-700 font-medium">{{ $r->collector->name }}</td>
            <td class="py-3 px-4 font-mono text-slate-500 font-bold">{{ $r->start_time }}</td>
            <td class="py-3 px-4 text-right font-extrabold text-[#0f1713]">{{ number_format($r->total_collected_liters, 2) }} L</td>
            <td class="py-3 px-4 text-right">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                    {{ $r->status === 'descargado' ? 'bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ $r->status }}
                </span>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>

{{-- Modal: asignar acopiador a zona --}}
<div id="modalRuta" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Asignar acopiador a zona</h3>
                <p class="text-[11px] text-slate-500">La salida del camión a las 4:30 de la mañana.</p>
            </div>
            <button type="button" data-cerrar-ruta class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
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
</div>

<script>
(function () {
    const modal = document.getElementById('modalRuta');

    document.querySelectorAll('[data-asignar-ruta]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = false; });
    });

    document.querySelectorAll('[data-cerrar-ruta]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = true; });
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) { modal.hidden = true; }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') { modal.hidden = true; }
    });

    @if($errors->any())
    modal.hidden = false;
    @endif
})();
</script>

@endsection
