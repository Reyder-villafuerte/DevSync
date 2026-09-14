@extends('layouts.app')

@section('title', 'Caudalímetro de Planta')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <span class="text-[10px] bg-sky-100 text-sky-800 font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Control de Descarga</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">
                Verificación con Caudalímetro en Planta
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                El Jefe de Producción valida los litros reales que entran al tanque. Solo esta cifra pasa al stock oficial.
            </p>
        </div>

        <div class="bg-slate-50 border border-slate-200 px-5 py-3 rounded-2xl text-right">
            <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Stock Leche Oficial</span>
            <span class="text-2xl font-black text-sky-900 tracking-tight">{{ number_format($stockLeche, 2) }} <span class="text-xs font-bold text-slate-500">L</span></span>
        </div>
    </div>

    <!-- Rutas pendientes y verificadas -->
    <div class="space-y-5">
        @forelse($routes as $route)
            <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-mono text-slate-400 font-bold uppercase">{{ $route->date }} • Salida 04:30 AM</span>
                        <h3 class="text-lg font-bold text-slate-900">{{ $route->zone->name }}</h3>
                        <p class="text-xs text-slate-500">Acopiador: <strong class="text-slate-800">{{ $route->collector->name }}</strong> ({{ $route->collector->phone }})</p>
                    </div>

                    <div class="flex items-center gap-4 text-xs">
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Anotado en Campo</span>
                            <span class="text-base font-bold text-slate-700">{{ number_format($route->total_collected_liters, 2) }} L</span>
                        </div>

                        @if($route->reception)
                        <div class="text-right">
                            <span class="text-[10px] text-sky-600 block uppercase font-bold">Caudalímetro (Planta)</span>
                            <span class="text-lg font-black text-sky-950">{{ number_format($route->reception->flowmeter_liters, 2) }} L</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Diferencia / Merma</span>
                            <span class="font-bold {{ $route->reception->difference_liters < 0 ? 'text-rose-600' : ($route->reception->difference_liters > 0 ? 'text-sky-600' : 'text-emerald-600') }}">
                                {{ $route->reception->difference_liters > 0 ? '+' : '' }}{{ number_format($route->reception->difference_liters, 2) }} L
                            </span>
                        </div>
                        
                        @if($route->reception->verification_status === 'incompleto')
                            <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-black bg-rose-100 text-rose-900 border border-rose-200">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Incompleto
                            </span>
                        @elseif($route->reception->verification_status === 'con_observacion')
                            <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-black bg-amber-100 text-amber-900 border border-amber-200">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i> Con Observación
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-black bg-emerald-100 text-emerald-900 border border-emerald-200">
                                <i class="fa-solid fa-check-double mr-1"></i> Verificado
                            </span>
                        @endif
                        @endif
                    </div>
                </div>

                @if($route->status === 'descargada_planta')
                <!-- Banner: Carga entregada esperando Caudalímetro -->
                <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-950 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-bell text-amber-600"></i>
                        <span><strong>Ruta cerrada por acopiador:</strong> <strong class="text-slate-900">{{ $route->collector->name }}</strong> ha entregado <strong>{{ number_format($route->total_collected_liters, 2) }} L</strong>. Ingrese la lectura real del caudalímetro. Solo esta cifra ingresará al stock oficial.</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-200 text-amber-950 shrink-0">
                        Esperando Caudalímetro
                    </span>
                </div>

                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                    <form action="{{ route('planta.verify', $route->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end text-xs">
                        @csrf
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-[10px] uppercase font-bold text-slate-700">Litros Caudalímetro (Planta) *</label>
                                <span id="diff-badge-{{ $route->id }}" class="text-[10px] font-mono font-bold text-slate-400"></span>
                            </div>
                            <input type="number" step="0.1" min="0" name="flowmeter_liters" id="flowmeter-{{ $route->id }}"
                                oninput="calcDifference({{ $route->id }}, {{ (float)$route->total_collected_liters }})"
                                placeholder="Ej. 150.0"
                                required class="w-full text-base font-black p-2.5 bg-white border-2 border-slate-200 focus:border-spark-lime rounded-xl text-slate-900 transition">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-700 mb-1">Veredicto Planta</label>
                            <select name="verification_status" id="status-{{ $route->id }}" required class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-800">
                                <option value="verificado">Verificado / Conforme</option>
                                <option value="incompleto">Incompleto / Desviación (Merma)</option>
                                <option value="con_observacion">Con Observación</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-700 mb-1">Observaciones / Motivo Merma</label>
                            <input type="text" name="observation" placeholder="Ej. Merma de 7L por espuma en porongos" class="w-full p-2.5 bg-white border border-slate-200 rounded-xl">
                        </div>

                        <div>
                            <button type="submit" class="w-full bg-[#0f1713] hover:bg-black text-[#bef264] font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">
                                Confirmar Ingreso a Stock
                            </button>
                        </div>
                    </form>
                </div>
                @elseif($route->status === 'verificada')
                <!-- Estado verificado con banner dinámico según estatus real -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 rounded-2xl border text-xs {{ $route->reception && $route->reception->verification_status === 'incompleto' ? 'bg-rose-50 border-rose-200 text-rose-950' : ($route->reception && $route->reception->verification_status === 'con_observacion' ? 'bg-amber-50 border-amber-200 text-amber-950' : 'bg-emerald-50 border-emerald-200 text-emerald-950') }}">
                    <div class="flex items-start sm:items-center gap-2.5">
                        @if($route->reception && $route->reception->verification_status === 'incompleto')
                            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base mt-0.5 sm:mt-0"></i>
                            <div>
                                <strong>Descarga Incompleta con Merma:</strong> Se confirmaron e ingresaron <strong>{{ number_format($route->reception->flowmeter_liters, 2) }} L</strong> al tanque. 
                                Merma registrada: <strong class="text-rose-700">{{ number_format($route->reception->difference_liters, 2) }} L</strong> frente a los {{ number_format($route->total_collected_liters, 2) }} L de campo.
                                @if($route->reception->observation)
                                    <span class="block sm:inline text-slate-600 italic mt-0.5 sm:mt-0">"{{ $route->reception->observation }}"</span>
                                @endif
                            </div>
                        @elseif($route->reception && $route->reception->verification_status === 'con_observacion')
                            <i class="fa-solid fa-circle-exclamation text-amber-600 text-base mt-0.5 sm:mt-0"></i>
                            <div>
                                <strong>Descarga con Observación:</strong> Se ingresaron <strong>{{ number_format($route->reception->flowmeter_liters, 2) }} L</strong> al stock. 
                                Observación: <span class="italic text-slate-700">"{{ $route->reception->observation }}"</span>
                            </div>
                        @else
                            <i class="fa-solid fa-circle-check text-emerald-600 text-base mt-0.5 sm:mt-0"></i>
                            <div>
                                <strong>Descarga Conforme:</strong> Se confirmó el ingreso de <strong>{{ number_format($route->reception ? $route->reception->flowmeter_liters : $route->total_collected_liters, 2) }} L</strong> al tanque de leche.
                                @if($route->reception && $route->reception->observation)
                                    <span class="text-slate-600 italic">"{{ $route->reception->observation }}"</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <button type="button" onclick="document.getElementById('edit-form-{{ $route->id }}').classList.toggle('hidden')"
                        class="shrink-0 px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                        <i class="fa-solid fa-pen-to-square text-slate-500"></i>
                        <span>Corregir Medición</span>
                    </button>
                </div>

                <!-- Formulario de corrección desplegable -->
                <div id="edit-form-{{ $route->id }}" class="hidden bg-slate-50 p-5 rounded-2xl border border-slate-200 mt-3">
                    <div class="flex items-center justify-between mb-3 border-b border-slate-200 pb-2">
                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-pen-to-square text-slate-500"></i> Corregir Medición de Caudalímetro
                        </h4>
                        <span class="text-[11px] text-slate-500">El stock oficial se ajustará automáticamente por la diferencia exacta.</span>
                    </div>

                    <form action="{{ route('planta.verify', $route->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end text-xs">
                        @csrf
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-[10px] uppercase font-bold text-slate-700">Litros Caudalímetro (Planta) *</label>
                                <span id="diff-badge-edit-{{ $route->id }}" class="text-[10px] font-mono font-bold text-slate-500">
                                    {{ $route->reception ? number_format($route->reception->difference_liters, 2) . ' L' : '' }}
                                </span>
                            </div>
                            <input type="number" step="0.1" min="0" name="flowmeter_liters" id="flowmeter-edit-{{ $route->id }}"
                                value="{{ $route->reception ? $route->reception->flowmeter_liters : $route->total_collected_liters }}"
                                oninput="calcDifferenceEdit({{ $route->id }}, {{ (float)$route->total_collected_liters }})"
                                required class="w-full text-base font-black p-2.5 bg-white border-2 border-slate-200 focus:border-spark-lime rounded-xl text-slate-900 transition">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-700 mb-1">Veredicto Planta</label>
                            <select name="verification_status" id="status-edit-{{ $route->id }}" required class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-800">
                                <option value="verificado" {{ $route->reception && $route->reception->verification_status === 'verificado' ? 'selected' : '' }}>Verificado / Conforme</option>
                                <option value="incompleto" {{ $route->reception && $route->reception->verification_status === 'incompleto' ? 'selected' : '' }}>Incompleto / Desviación (Merma)</option>
                                <option value="con_observacion" {{ $route->reception && $route->reception->verification_status === 'con_observacion' ? 'selected' : '' }}>Con Observación</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-700 mb-1">Observaciones / Motivo Merma</label>
                            <input type="text" name="observation" value="{{ $route->reception ? $route->reception->observation : '' }}"
                                placeholder="Ej. Merma de 7L por espuma en porongos" class="w-full p-2.5 bg-white border border-slate-200 rounded-xl">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="w-full bg-[#0f1713] hover:bg-black text-[#bef264] font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">
                                Actualizar y Ajustar Stock
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-500 flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-slate-400"></i>
                    <span>El camión se encuentra en ruta de campo (Salida 04:30 AM). Cuando el acopiador presione <strong>"Cerrar Ruta"</strong>, la carga aparecerá aquí lista para su medición por caudalímetro.</span>
                </div>
                @endif
            </div>
        @empty
            <div class="bg-white p-10 rounded-3xl border border-slate-200 text-center text-slate-400 text-xs">
                No hay descargas registradas en planta para hoy.
            </div>
        @endforelse
    </div>
</div>

<script>
    function calcDifference(routeId, fieldLiters) {
        const input = document.getElementById('flowmeter-' + routeId);
        const badge = document.getElementById('diff-badge-' + routeId);
        const statusSelect = document.getElementById('status-' + routeId);
        
        if (!input || !badge) return;
        const val = parseFloat(input.value);
        if (isNaN(val)) {
            badge.textContent = '';
            return;
        }

        const diff = val - fieldLiters;
        if (diff < -0.01) {
            badge.textContent = 'Merma: ' + diff.toFixed(2) + ' L';
            badge.className = 'text-[10px] font-mono font-bold text-rose-600';
            if (statusSelect) statusSelect.value = 'incompleto';
        } else if (diff > 0.01) {
            badge.textContent = '+' + diff.toFixed(2) + ' L';
            badge.className = 'text-[10px] font-mono font-bold text-sky-600';
            if (statusSelect) statusSelect.value = 'verificado';
        } else {
            badge.textContent = 'Exacto (0.00 L)';
            badge.className = 'text-[10px] font-mono font-bold text-emerald-600';
            if (statusSelect) statusSelect.value = 'verificado';
        }
    }

    function calcDifferenceEdit(routeId, fieldLiters) {
        const input = document.getElementById('flowmeter-edit-' + routeId);
        const badge = document.getElementById('diff-badge-edit-' + routeId);
        const statusSelect = document.getElementById('status-edit-' + routeId);
        
        if (!input || !badge) return;
        const val = parseFloat(input.value);
        if (isNaN(val)) {
            badge.textContent = '';
            return;
        }

        const diff = val - fieldLiters;
        if (diff < -0.01) {
            badge.textContent = 'Merma: ' + diff.toFixed(2) + ' L';
            badge.className = 'text-[10px] font-mono font-bold text-rose-600';
            if (statusSelect) statusSelect.value = 'incompleto';
        } else if (diff > 0.01) {
            badge.textContent = '+' + diff.toFixed(2) + ' L';
            badge.className = 'text-[10px] font-mono font-bold text-sky-600';
            if (statusSelect) statusSelect.value = 'verificado';
        } else {
            badge.textContent = 'Exacto (0.00 L)';
            badge.className = 'text-[10px] font-mono font-bold text-emerald-600';
            if (statusSelect) statusSelect.value = 'verificado';
        }
    }
</script>
@endsection

