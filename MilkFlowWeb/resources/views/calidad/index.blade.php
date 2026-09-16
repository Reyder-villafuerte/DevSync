@extends('layouts.app')

@section('title', 'Control de Calidad Lactoscan')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-vial-circle-check"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Control de Calidad Lactoscan</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Físico-Químico</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Evaluación de densidad, grasa, acidez y agua añadida. Agenda visitas técnicas automáticas ante desviaciones.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 {{ Auth::user()->role === 'inspector_calidad' ? 'lg:grid-cols-3' : '' }} gap-6">
        @if(Auth::user()->role === 'inspector_calidad')
        <!-- Formulario Lactoscan -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-flask"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Registrar Prueba Lactoscan</h3>
            </div>

            <form action="{{ route('calidad.analysis.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- 1. Selector de las 4 Zonas de Huata -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>1. Filtrar por Zona de Huata</span>
                        <span class="text-[10px] font-semibold text-slate-400">4 Zonas</span>
                    </label>
                    <div class="grid grid-cols-5 gap-1.5 p-1 bg-slate-100/80 rounded-2xl mb-2">
                        <button type="button" onclick="filterProducersByZone('all')" id="zoneBtn_all" class="zone-pill py-1.5 text-[11px] font-bold rounded-xl bg-[#0f1713] text-[#bef264] shadow-sm transition">
                            Todas
                        </button>
                        @foreach($zones as $z)
                            <button type="button" onclick="filterProducersByZone('{{ $z->id }}', '{{ $z->name }}')" id="zoneBtn_{{ $z->id }}" class="zone-pill py-1.5 text-[11px] font-bold rounded-xl text-slate-600 hover:text-slate-900 transition">
                                Z-{{ $loop->iteration }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Seleccionar Productor (Cruzado con Acopio de Hoy) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">2. Productor Evaluado</label>
                        <span id="zoneIndicatorBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                            Todas las zonas (<span id="visibleProducersCount">{{ count($producers) }}</span>)
                        </span>
                    </div>

                    <!-- Buscador reactivo dentro de los productores de la zona -->
                    <div class="relative mb-2">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <input type="text" id="producerSearchInput" placeholder="Buscar por nombre o DNI..." class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50/70 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>

                    <select name="producer_id" id="producerSelect" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                        @foreach($producers as $p)
                            <option value="{{ $p->id }}" 
                                    data-zone="{{ $p->today_zone_id ?? $p->zone_id }}" 
                                    data-name="{{ strtolower($p->name) }}" 
                                    data-dni="{{ $p->dni }}" 
                                    data-liters="{{ $p->today_liters }}">
                                {{ $p->name }} ({{ $p->zone ? $p->zone->name : 'Sin zona' }})
                                @if($p->today_liters !== null)
                                    — 🥛 Acopiado hoy: {{ $p->today_liters }} L
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Se muestran los registros del acopiador para la zona seleccionada.
                    </p>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha de Análisis</label>
                    <input type="date" name="analysis_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Grasa (%)</label>
                        <input type="number" step="0.01" name="fat_percentage" placeholder="3.5" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Sólidos No Grasos</label>
                        <input type="number" step="0.01" name="snf_percentage" placeholder="8.4" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Densidad (g/cm³)</label>
                        <input type="number" step="0.01" name="density" placeholder="1.029" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Proteína (%)</label>
                        <input type="number" step="0.01" name="protein_percentage" placeholder="3.1" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Agua Añadida (%)</label>
                        <input type="number" step="0.01" name="water_addition_percentage" placeholder="0.0" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Temperatura (°C)</label>
                        <input type="number" step="0.01" name="temperature" placeholder="15.0" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">pH / Acidez (°D)</label>
                        <input type="number" step="0.01" name="ph_or_acidity" placeholder="17.0" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Veredicto del Análisis</label>
                    <select name="verdict" id="verdictSelect" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 font-bold text-slate-800 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                        <option value="conforme">Conforme (Leche Óptima)</option>
                        <option value="acidez_alta">Acidez Alta (Requiere Visita Técnica)</option>
                        <option value="adulterada">Sospecha de Adulteración / Agua</option>
                        <option value="sospechosa">Sospechosa / En Observación</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Observaciones / Notas de Muestra</label>
                    <textarea name="notes" rows="2" placeholder="Detalles de la muestra o indicaciones de inspección..." class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 resize-none"></textarea>
                </div>

                <!-- Box para agendar cita técnica si hay anomalía -->
                <div id="visitBox" class="p-4 bg-amber-500/10 rounded-2xl border border-amber-500/20 text-xs space-y-3">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="schedule_visit" value="1" id="scheduleVisitCheck" class="rounded border-amber-300 text-amber-900 focus:ring-0">
                        <label for="scheduleVisitCheck" class="font-bold text-amber-900 cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-calendar-plus text-amber-600"></i> Agendar Visita Técnica al Productor
                        </label>
                    </div>
                    <div id="visitFields" class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Fecha Propuesta de Visita</label>
                            <input type="date" name="scheduled_date" value="{{ date('Y-m-d', strtotime('+2 days')) }}" class="w-full text-xs p-2.5 rounded-xl border border-amber-200 bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Motivo de Visita</label>
                            <input type="text" name="visit_reason" placeholder="Revisar higiene de ordeño o refrigeración" class="w-full text-xs p-2.5 rounded-xl border border-amber-200 bg-white">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold py-3 rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Guardar Análisis y Notificar
                </button>
            </form>
        </div>
        @endif

        <!-- Historial de pruebas Lactoscan con Buscador y Filtros -->
        <div class="{{ Auth::user()->role === 'inspector_calidad' ? 'lg:col-span-2' : 'w-full' }} bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-table-list"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Historial de Evaluaciones Lactoscan</h3>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Total: {{ $analyses->total() }} registros</span>
                </div>

                <!-- Buscador y Filtros por Veredicto (Conformes, Acidez, Adulterada, Todos) -->
                <form method="GET" action="{{ route('calidad.index') }}" class="mb-5 space-y-3">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por productor o DNI..." class="w-full text-xs pl-9 pr-3 py-2.5 rounded-2xl border border-slate-200 bg-slate-50/70 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                        </div>
                        <select name="zone_id" onchange="this.form.submit()" class="text-xs py-2.5 px-3 rounded-2xl border border-slate-200 bg-slate-50/70 focus:bg-white text-slate-700">
                            <option value="">Todas las Zonas</option>
                            @foreach($zones as $z)
                                <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2.5 bg-[#0f1713] text-[#bef264] text-xs font-bold rounded-2xl hover:bg-slate-900 transition flex items-center gap-1.5 justify-center">
                            <i class="fa-solid fa-filter text-[10px]"></i> Filtrar
                        </button>
                        @if(request('search') || request('verdict') || request('zone_id'))
                            <a href="{{ route('calidad.index') }}" class="px-3 py-2.5 bg-slate-100 text-slate-600 text-xs font-bold rounded-2xl hover:bg-slate-200 transition flex items-center justify-center" title="Limpiar filtros">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        @endif
                    </div>

                    <!-- Píldoras de Veredicto -->
                    <div class="flex flex-wrap gap-1.5 items-center pt-2 border-t border-slate-100">
                        <a href="{{ route('calidad.index', array_merge(request()->except('verdict'), [])) }}" 
                           class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 {{ !request('verdict') ? 'bg-[#0f1713] text-[#bef264]' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            <span>Todos</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ !request('verdict') ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $counts['all'] }}</span>
                        </a>
                        <a href="{{ route('calidad.index', array_merge(request()->except('verdict'), ['verdict' => 'conforme'])) }}" 
                           class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 {{ request('verdict') === 'conforme' ? 'bg-[#bef264] text-[#0f1713] border border-[#0f1713]' : 'bg-[#bef264]/20 text-[#0f1713] hover:bg-[#bef264]/40 border border-[#bef264]/40' }}">
                            <i class="fa-solid fa-circle-check text-[10px]"></i> Conformes
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white text-[#0f1713] font-black">{{ $counts['conforme'] }}</span>
                        </a>
                        <a href="{{ route('calidad.index', array_merge(request()->except('verdict'), ['verdict' => 'acidez_alta'])) }}" 
                           class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 {{ request('verdict') === 'acidez_alta' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Acidez Alta
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/90 text-amber-900 font-black">{{ $counts['acidez_alta'] }}</span>
                        </a>
                        <a href="{{ route('calidad.index', array_merge(request()->except('verdict'), ['verdict' => 'adulterada'])) }}" 
                           class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 {{ request('verdict') === 'adulterada' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' }}">
                            <i class="fa-solid fa-droplet-slash text-[10px]"></i> Adulteración (Agua)
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/90 text-rose-900 font-black">{{ $counts['adulterada'] }}</span>
                        </a>
                        @if($counts['sospechosa'] > 0)
                        <a href="{{ route('calidad.index', array_merge(request()->except('verdict'), ['verdict' => 'sospechosa'])) }}" 
                           class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 {{ request('verdict') === 'sospechosa' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200' }}">
                            <i class="fa-solid fa-eye text-[10px]"></i> Sospechosa
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/90 text-purple-900 font-black">{{ $counts['sospechosa'] }}</span>
                        </a>
                        @endif
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="pb-3 px-3">Fecha</th>
                                <th class="pb-3 px-3">Productor</th>
                                <th class="pb-3 px-3">Zona</th>
                                <th class="pb-3 px-3">Parámetros</th>
                                <th class="pb-3 px-3">Veredicto</th>
                                <th class="pb-3 px-3">Cita Técnica</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($analyses as $ana)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-3 text-slate-500 font-medium">{{ $ana->analysis_date }}</td>
                                <td class="py-3.5 px-3">
                                    <div class="font-bold text-slate-800">{{ $ana->producer->name }}</div>
                                    <div class="text-[10px] text-slate-400">DNI: {{ $ana->producer->dni ?: '—' }}</div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        {{ $ana->producer->zone ? $ana->producer->zone->name : '—' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-[11px] text-slate-600">
                                    <span class="text-slate-400 font-semibold">G:</span> {{ $ana->fat_percentage ?? '—' }}% · 
                                    <span class="text-slate-400 font-semibold">D:</span> {{ $ana->density ?? '—' }} · 
                                    <span class="text-slate-400 font-semibold">Ac:</span> {{ $ana->ph_or_acidity ?? '—' }}
                                    @if($ana->water_addition_percentage > 0)
                                        · <span class="text-rose-600 font-bold">Agua: {{ $ana->water_addition_percentage }}%</span>
                                    @endif
                                    @if($ana->temperature) 
                                        · <span class="text-slate-400 font-semibold">T:</span> {{ $ana->temperature }}°C 
                                    @endif
                                </td>
                                <td class="py-3.5 px-3">
                                    @if($ana->verdict === 'conforme')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/60">
                                            Conforme
                                        </span>
                                    @elseif($ana->verdict === 'acidez_alta')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 uppercase">
                                            Acidez Alta
                                        </span>
                                    @elseif($ana->verdict === 'adulterada')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 uppercase">
                                            Adulterada (Agua)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 uppercase">
                                            {{ str_replace('_', ' ', $ana->verdict) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3">
                                    @if($ana->technicalVisits->count() > 0)
                                        @foreach($ana->technicalVisits as $tv)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tv->status === 'realizada' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                                <i class="fa-solid {{ $tv->status === 'realizada' ? 'fa-check' : 'fa-calendar-check' }} text-[9px]"></i> 
                                                {{ $tv->scheduled_date }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-slate-400 text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                                    <i class="fa-solid fa-inbox text-2xl text-slate-300 block mb-2"></i>
                                    No se encontraron análisis que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100">
                {{ $analyses->links() }}
            </div>
        </div>
    </div>

    <!-- 3. Registro de Visitas y Citas Técnicas del Día (Abajo de Lactoscan) -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-700 flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-black text-slate-900">Agenda de Visitas Técnicas de Hoy</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                            {{ date('d/m/Y') }} · {{ $todayVisits->count() }} citas
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Seguimiento técnico a productores con acidez elevada o anomalías detectadas en ruta de acopio.
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Horario</th>
                        <th class="pb-3 px-3">Productor</th>
                        <th class="pb-3 px-3">Zona</th>
                        <th class="pb-3 px-3">Motivo / Causa de Calidad</th>
                        <th class="pb-3 px-3">Inspector Responsable</th>
                        <th class="pb-3 px-3">Estado</th>
                        <th class="pb-3 px-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($todayVisits as $visit)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 font-bold text-slate-700 whitespace-nowrap">
                            <i class="fa-regular fa-clock text-slate-400 mr-1"></i>
                            {{ $visit->scheduled_time ? substr($visit->scheduled_time, 0, 5) : '09:00' }}
                        </td>
                        <td class="py-3.5 px-3">
                            <div class="font-bold text-slate-800">{{ $visit->producer->name }}</div>
                            <div class="text-[10px] text-slate-400">DNI: {{ $visit->producer->dni ?: '—' }} · Tel: {{ $visit->producer->phone ?: '—' }}</div>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                {{ $visit->producer->zone ? $visit->producer->zone->name : 'Sin zona' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-slate-600 max-w-xs">
                            <div class="font-medium text-slate-800">{{ $visit->reason }}</div>
                            @if($visit->resolution_report)
                                <div class="mt-1.5 text-[11px] text-emerald-800 bg-emerald-50/80 p-2.5 rounded-xl border border-emerald-200">
                                    <span class="font-bold text-emerald-900 block mb-0.5"><i class="fa-solid fa-clipboard-check text-emerald-600"></i> Informe de Resolución:</span>
                                    {{ $visit->resolution_report }}
                                </div>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-slate-600">
                            {{ $visit->inspector ? $visit->inspector->name : 'Inspector asignado' }}
                        </td>
                        <td class="py-3.5 px-3">
                            @if($visit->status === 'realizada')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1 w-fit">
                                    <i class="fa-solid fa-check"></i> Realizada
                                </span>
                            @elseif($visit->status === 'cancelada')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 flex items-center gap-1 w-fit">
                                    <i class="fa-solid fa-ban"></i> Cancelada
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1 w-fit">
                                    <i class="fa-regular fa-hourglass-half"></i> Programada
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            @if($visit->status === 'programada')
                                <button type="button" onclick="openCompleteVisitModal({{ $visit->id }}, '{{ addslashes($visit->producer->name) }}')" class="px-3 py-1.5 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 ml-auto shadow-sm">
                                    <i class="fa-solid fa-clipboard-check"></i> Marcar Realizada
                                </button>
                            @else
                                <span class="text-[11px] text-emerald-700 font-bold flex items-center justify-end gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Atendida
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 font-medium">
                            <i class="fa-solid fa-calendar-check text-2xl text-slate-300 block mb-2"></i>
                            No hay citas ni visitas técnicas agendadas para el día de hoy ({{ date('d/m/Y') }}).
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Registrar Informe de Resolución de Visita Técnica -->
<div id="completeVisitModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 border border-slate-100 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Completar Visita Técnica</h3>
                    <p id="modalProducerName" class="text-xs text-slate-500 font-semibold"></p>
                </div>
            </div>
            <button type="button" onclick="closeCompleteVisitModal()" class="w-7 h-7 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="completeVisitForm" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Informe de Resolución / Hallazgos en Campo</label>
                <textarea name="resolution_report" required rows="4" placeholder="Ej: Se capacitó en higiene de cantinas y se calibró la temperatura del tanque de enfriamiento. Muestra tomada arrojó 16°D normal." class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-[#0f1713] focus:ring-0 resize-none"></textarea>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" onclick="closeCompleteVisitModal()" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] text-xs font-bold rounded-2xl transition flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-check"></i> Guardar y Cerrar Cita
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentSelectedZone = 'all';

function filterProducersByZone(zoneId, zoneName = 'Todas las zonas') {
    currentSelectedZone = zoneId;

    // Actualizar estilo visual de los botones de zona
    document.querySelectorAll('.zone-pill').forEach(btn => {
        btn.classList.remove('bg-[#0f1713]', 'text-[#bef264]', 'shadow-sm');
        btn.classList.add('text-slate-600');
    });

    const activeBtn = document.getElementById('zoneBtn_' + zoneId);
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-600');
        activeBtn.classList.add('bg-[#0f1713]', 'text-[#bef264]', 'shadow-sm');
    }

    // Actualizar badge indicador
    const badge = document.getElementById('zoneIndicatorBadge');
    if (badge) {
        badge.innerHTML = (zoneId === 'all' ? 'Todas las zonas' : zoneName) + ' (<span id="visibleProducersCount">0</span>)';
    }

    applyProducersFilter();
}

function applyProducersFilter() {
    const searchInput = document.getElementById('producerSearchInput');
    const select = document.getElementById('producerSelect');
    if (!searchInput || !select) return;

    const searchVal = (searchInput.value || '').trim().toLowerCase();
    const options = Array.from(select.options);
    let visibleCount = 0;
    let firstVisibleIndex = -1;

    options.forEach((opt, idx) => {
        const optZone = opt.getAttribute('data-zone');
        const optName = opt.getAttribute('data-name') || '';
        const optDni = opt.getAttribute('data-dni') || '';

        const matchesZone = (currentSelectedZone === 'all' || optZone === currentSelectedZone);
        const matchesSearch = !searchVal || optName.includes(searchVal) || optDni.includes(searchVal);

        if (matchesZone && matchesSearch) {
            opt.hidden = false;
            opt.disabled = false;
            visibleCount++;
            if (firstVisibleIndex === -1) {
                firstVisibleIndex = idx;
            }
        } else {
            opt.hidden = true;
            opt.disabled = true;
        }
    });

    const countElem = document.getElementById('visibleProducersCount');
    if (countElem) {
        countElem.innerText = visibleCount;
    }

    if (firstVisibleIndex !== -1 && options[select.selectedIndex]?.disabled) {
        select.selectedIndex = firstVisibleIndex;
    }
}

function openCompleteVisitModal(visitId, producerName) {
    const modal = document.getElementById('completeVisitModal');
    const form = document.getElementById('completeVisitForm');
    const nameLabel = document.getElementById('modalProducerName');

    form.action = `/calidad/cita/${visitId}/completar`;
    nameLabel.innerText = `Productor: ${producerName}`;
    modal.classList.remove('hidden');
}

function closeCompleteVisitModal() {
    document.getElementById('completeVisitModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    // Sincronizar búsqueda de productores
    const searchInput = document.getElementById('producerSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyProducersFilter);
    }

    // Sincronizar veredicto con cita técnica
    const verdictSelect = document.getElementById('verdictSelect');
    const scheduleVisitCheck = document.getElementById('scheduleVisitCheck');

    if (verdictSelect && scheduleVisitCheck) {
        verdictSelect.addEventListener('change', () => {
            if (verdictSelect.value === 'acidez_alta') {
                scheduleVisitCheck.checked = true;
            }
        });
    }

    applyProducersFilter();
});
</script>
@endsection
