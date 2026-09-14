@extends('layouts.app')

@section('title', 'Historial y Reportes de Acopio')

@section('content')
<div class="space-y-8">
    <!-- Header Spark Style -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[10px] bg-spark-lime/20 text-spark-limeText font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    Reportes Operativos
                </span>
                <span class="text-xs text-slate-400">Distrito de Huata</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-700"></i>
                Historial y Reportes de Acopio
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Trazabilidad completa de rutas matutinas (4:30 AM), balance de leche en campo vs caudalímetro de planta y registro de mermas.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('acopio.index') }}" 
               class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-truck-fast"></i>
                <span>Ir a Planilla Activa</span>
            </a>
            <button type="button" onclick="window.print()" 
               class="px-5 py-2.5 rounded-2xl bg-[#0f1713] hover:bg-black text-[#bef264] text-xs font-bold transition flex items-center gap-2 shadow-sm cursor-pointer">
                <i class="fa-solid fa-print"></i>
                <span>Imprimir Reporte</span>
            </button>
        </div>
    </div>

    <!-- Tarjetas de Métricas Spark Admin -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Rutas -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Rutas Realizadas</span>
                <span class="text-2xl font-black text-slate-900 tracking-tight">{{ $totalRoutes }}</span>
                <span class="text-[10px] text-slate-500 block mt-0.5">Turnos de campo</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-700 text-lg">
                <i class="fa-solid fa-route"></i>
            </div>
        </div>

        <!-- Card 2: Litros Campo -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Anotado (Campo)</span>
                <span class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($totalFieldLiters, 1) }} <span class="text-xs font-bold text-slate-400">L</span></span>
                <span class="text-[10px] text-slate-500 block mt-0.5">Declarado en porongos</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-[#bef264]/20 border border-[#bef264]/30 flex items-center justify-center text-[#0f1713] text-lg">
                <i class="fa-solid fa-bottle-droplet"></i>
            </div>
        </div>

        <!-- Card 3: Litros Caudalímetro -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Caudalímetro (Planta)</span>
                <span class="text-2xl font-black text-emerald-800 tracking-tight">{{ number_format($totalFlowmeterLiters, 1) }} <span class="text-xs font-bold text-slate-400">L</span></span>
                <span class="text-[10px] text-slate-500 block mt-0.5">Medición real validada</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700 text-lg">
                <i class="fa-solid fa-gauge-high"></i>
            </div>
        </div>

        <!-- Card 4: Balance Neto Merma -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Balance Neto (Merma)</span>
                @if($totalLossLiters < -0.01)
                    <span class="text-2xl font-black text-rose-600 tracking-tight">{{ number_format($totalLossLiters, 1) }} <span class="text-xs font-bold text-rose-400">L</span></span>
                    <span class="text-[10px] text-rose-600 font-bold block mt-0.5">Merma en descarga</span>
                @elseif($totalLossLiters > 0.01)
                    <span class="text-2xl font-black text-sky-600 tracking-tight">+{{ number_format($totalLossLiters, 1) }} <span class="text-xs font-bold text-sky-400">L</span></span>
                    <span class="text-[10px] text-sky-600 font-bold block mt-0.5">Favorable a planta</span>
                @else
                    <span class="text-2xl font-black text-emerald-700 tracking-tight">0.0 <span class="text-xs font-bold text-emerald-500">L</span></span>
                    <span class="text-[10px] text-emerald-600 font-bold block mt-0.5">Cuadre exacto</span>
                @endif
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 text-lg">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros Spark Style -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-400 mr-2 uppercase tracking-wider">Período:</span>
            <a href="{{ route('acopio.historial', ['period' => 'today']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'today' ? 'bg-[#0f1713] text-[#bef264]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700' }}">
                Hoy
            </a>
            <a href="{{ route('acopio.historial', ['period' => 'week']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'week' ? 'bg-[#0f1713] text-[#bef264]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700' }}">
                Esta Semana
            </a>
            <a href="{{ route('acopio.historial', ['period' => 'month']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'month' ? 'bg-[#0f1713] text-[#bef264]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700' }}">
                Este Mes
            </a>
            <a href="{{ route('acopio.historial', ['period' => 'all']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'all' ? 'bg-[#0f1713] text-[#bef264]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700' }}">
                Histórico Completo
            </a>
        </div>

        <form action="{{ route('acopio.historial') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="start_date" value="{{ request('start_date') }}" 
                   class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700">
            <span class="text-slate-400 text-xs">a</span>
            <input type="date" name="end_date" value="{{ request('end_date') }}" 
                   class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700">
            <button type="submit" class="px-4 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-black transition cursor-pointer">
                Filtrar
            </button>
        </form>
    </div>

    <!-- Tabla Detallada de Historial de Rutas -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Registro de Rutas y Observaciones de Planta</h3>
                <p class="text-xs text-slate-500">Muestra el detalle fecha por fecha, los litros auditados en caudalímetro y notas técnicas.</p>
            </div>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-full">
                {{ $routes->count() }} registros
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5 rounded-l-xl">Fecha</th>
                        <th class="p-3.5">Zona Asignada</th>
                        <th class="p-3.5">Acopiador</th>
                        <th class="p-3.5">Litros Campo</th>
                        <th class="p-3.5">Caudalímetro Planta</th>
                        <th class="p-3.5">Diferencia / Merma</th>
                        <th class="p-3.5">Observaciones de Planta</th>
                        <th class="p-3.5">Estado</th>
                        <th class="p-3.5 rounded-r-xl text-center">Ver Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($routes as $route)
                        @php
                            $rec = $route->reception;
                            $fieldLiters = (float)$route->total_collected_liters;
                            $plantLiters = $rec ? (float)$rec->flowmeter_liters : null;
                            $diff = $plantLiters !== null ? ($plantLiters - $fieldLiters) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3.5 font-mono text-slate-700 font-bold">
                                {{ \Carbon\Carbon::parse($route->date)->format('d/m/Y') }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-800">
                                {{ $route->zone ? $route->zone->name : 'Zona Huata' }}
                            </td>
                            <td class="p-3.5 font-medium text-slate-600">
                                {{ $route->collector ? $route->collector->name : '—' }}
                            </td>
                            <td class="p-3.5 font-black text-slate-900">
                                {{ number_format($fieldLiters, 2) }} L
                            </td>
                            <td class="p-3.5 font-black">
                                @if($plantLiters !== null)
                                    <span class="text-slate-900">{{ number_format($plantLiters, 2) }} L</span>
                                @else
                                    <span class="text-slate-400 italic">Pendiente</span>
                                @endif
                            </td>
                            <td class="p-3.5 font-bold">
                                @if($diff !== null)
                                    @if($diff < -0.01)
                                        <span class="text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full inline-flex items-center gap-1 font-mono text-[11px]">
                                            <i class="fa-solid fa-arrow-down text-[9px]"></i> {{ number_format($diff, 2) }} L (Merma)
                                        </span>
                                    @elseif($diff > 0.01)
                                        <span class="text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-full inline-flex items-center gap-1 font-mono text-[11px]">
                                            <i class="fa-solid fa-arrow-up text-[9px]"></i> +{{ number_format($diff, 2) }} L
                                        </span>
                                    @else
                                        <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full inline-flex items-center gap-1 font-mono text-[11px]">
                                            <i class="fa-solid fa-check text-[9px]"></i> 0.00 L (Exacto)
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-700 max-w-xs">
                                @if($rec && $rec->observation)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-800 bg-slate-100/80 px-2.5 py-1 rounded-xl">
                                        <i class="fa-solid fa-comment-dots text-slate-500"></i>
                                        <span>{{ $rec->observation }}</span>
                                    </span>
                                @elseif($rec)
                                    <span class="text-emerald-700 font-medium inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-emerald-500 text-xs"></i> Todo conforme
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">En espera de verificación</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                @if($rec && $rec->verification_status === 'incompleto')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Incompleto
                                    </span>
                                @elseif($rec && $rec->verification_status === 'con_observacion')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-exclamation"></i> Con Observación
                                    </span>
                                @elseif($route->status === 'verificada')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check-double"></i> Conforme
                                    </span>
                                @elseif($route->status === 'descargada_planta')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-clock-rotate-left"></i> En Planta
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-lime-100 text-[#0f1713] border border-lime-300 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-truck-fast"></i> En Ruta
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 text-center">
                                @php
                                    $detailData = [
                                        'date' => \Carbon\Carbon::parse($route->date)->format('d/m/Y'),
                                        'zone' => $route->zone ? $route->zone->name : 'Zona Huata',
                                        'collector' => $route->collector ? $route->collector->name : 'Acopiador',
                                        'status' => $rec ? $rec->verification_status : $route->status,
                                        'field_liters' => number_format($fieldLiters, 2),
                                        'plant_liters' => $plantLiters !== null ? number_format($plantLiters, 2) : 'Pendiente',
                                        'difference' => $diff !== null ? number_format($diff, 2) . ' L' : 'Pendiente',
                                        'observation' => ($rec && $rec->observation) ? $rec->observation : ($rec ? 'Todo conforme' : 'En espera de revisión en planta'),
                                        'verifier' => ($rec && $rec->verifier) ? $rec->verifier->name : 'Jefe de Producción',
                                        'records' => $route->records->map(function($r) {
                                            return [
                                                'producer_name' => $r->producer ? $r->producer->name : 'Proveedor Huata',
                                                'producer_dni' => $r->producer ? ($r->producer->dni ?: '—') : '—',
                                                'liters' => number_format($r->liters, 2),
                                                'time' => $r->collected_at ?: '—',
                                                'notes' => $r->notes ?: '—'
                                            ];
                                        })
                                    ];
                                @endphp
                                <button type="button"
                                    onclick="openHistoryModal({{ json_encode($detailData) }})"
                                    class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-[#0f1713] hover:text-[#bef264] text-slate-700 transition flex items-center justify-center mx-auto cursor-pointer shadow-sm"
                                    title="Ver desglose de proveedores">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                No se encontraron registros de rutas para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: DETALLE HISTÓRICO DE RUTA (OJITO) -->
<div id="historyModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="history-modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeHistoryModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200">
            <!-- Header Modal Historial -->
            <div class="bg-slate-900 px-6 py-5 text-white flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#bef264]/20 border border-[#bef264]/30 flex items-center justify-center text-[#bef264]">
                        <i class="fa-solid fa-clipboard-list text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black tracking-tight">Detalle de Ruta & Cuadre de Planta</h3>
                            <span id="histVerdictBadge" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase"></span>
                        </div>
                        <p class="text-[11px] text-slate-400" id="histHeaderSubtitle">Ruta Huata</p>
                    </div>
                </div>
                <button type="button" onclick="closeHistoryModal()" class="text-slate-400 hover:text-white transition text-lg cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-5">
                <!-- Resumen de Cuadre Spark Style -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Campo</span>
                        <span class="text-lg font-black text-slate-900" id="histFieldLiters">—</span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Caudalímetro</span>
                        <span class="text-lg font-black text-slate-900" id="histPlantLiters">—</span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Diferencia / Merma</span>
                        <span class="text-lg font-black" id="histDifference">—</span>
                    </div>
                </div>

                <!-- Observaciones del Jefe de Producción -->
                <div class="bg-amber-50/60 p-4 rounded-2xl border border-amber-200/70">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-comment-dots text-amber-700 text-xs"></i>
                        <span class="text-xs font-bold text-amber-900 uppercase tracking-wider">Observaciones del Jefe de Planta</span>
                    </div>
                    <p class="text-xs text-slate-700 italic font-medium" id="histObservation">—</p>
                    <p class="text-[10px] text-slate-500 mt-1" id="histVerifier">Verificado por: —</p>
                </div>

                <!-- Tabla de Entregas por Proveedor de esa Ruta -->
                <div>
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2.5">
                        Proveedores y Litros Acopiados en esta Ruta
                    </h4>
                    <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-2xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-bold sticky top-0">
                                <tr>
                                    <th class="p-2.5">Proveedor</th>
                                    <th class="p-2.5">DNI</th>
                                    <th class="p-2.5">Litros</th>
                                    <th class="p-2.5">Hora</th>
                                    <th class="p-2.5">Notas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100" id="histRecordsTableBody">
                                <!-- Dinámico -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="button" onclick="closeHistoryModal()"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 transition cursor-pointer">
                        Cerrar Detalle
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openHistoryModal(data) {
        document.getElementById('histHeaderSubtitle').textContent = (data.zone || 'Huata') + ' • ' + data.date + (data.collector ? (' • ' + data.collector) : '');
        document.getElementById('histFieldLiters').textContent = data.field_liters + ' L';
        document.getElementById('histPlantLiters').textContent = data.plant_liters !== 'Pendiente' ? (data.plant_liters + ' L') : 'Pendiente';
        
        const diffElem = document.getElementById('histDifference');
        diffElem.textContent = data.difference;
        if (data.difference.startsWith('-')) {
            diffElem.className = 'text-lg font-black text-rose-600';
        } else if (data.difference.startsWith('+')) {
            diffElem.className = 'text-lg font-black text-sky-600';
        } else {
            diffElem.className = 'text-lg font-black text-emerald-700';
        }

        const verdictElem = document.getElementById('histVerdictBadge');
        if (data.status === 'incompleto') {
            verdictElem.textContent = 'INCOMPLETO / MERMA';
            verdictElem.className = 'px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-500 text-white';
        } else if (data.status === 'con_observacion') {
            verdictElem.textContent = 'CON OBSERVACIÓN';
            verdictElem.className = 'px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-amber-500 text-white';
        } else if (data.status === 'verificado' || data.status === 'verificada') {
            verdictElem.textContent = 'CONFORME';
            verdictElem.className = 'px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-500 text-white';
        } else {
            verdictElem.textContent = 'EN RUTA';
            verdictElem.className = 'px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-lime-400 text-slate-900';
        }

        document.getElementById('histObservation').textContent = data.observation;
        document.getElementById('histVerifier').textContent = 'Verificado por: ' + data.verifier;

        const tbody = document.getElementById('histRecordsTableBody');
        tbody.innerHTML = '';

        if (data.records && data.records.length > 0) {
            data.records.forEach(r => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50 transition';
                tr.innerHTML = `
                    <td class="p-2.5 font-bold text-slate-800">${r.producer_name}</td>
                    <td class="p-2.5 font-mono text-slate-600">${r.producer_dni}</td>
                    <td class="p-2.5 font-black text-emerald-700">${r.liters} L</td>
                    <td class="p-2.5 font-mono text-slate-500">${r.time}</td>
                    <td class="p-2.5 text-slate-500 italic truncate max-w-xs">${r.notes}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="p-4 text-center text-slate-400">No hay entregas individuales registradas en esta ruta.</td></tr>`;
        }

        document.getElementById('historyModal').classList.remove('hidden');
    }

    function closeHistoryModal() {
        document.getElementById('historyModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeHistoryModal();
        }
    });
</script>
@endsection
