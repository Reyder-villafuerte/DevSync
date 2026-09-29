@extends('layouts.app')

@section('title', 'Acopio en Ruta - 4:30 AM')

@section('content')
<div class="space-y-8">
    <!-- Header de Módulo Spark Style -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[10px] bg-spark-lime/20 text-spark-limeText font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    Ruta Activa 4:30 AM
                </span>
                <span class="text-xs text-slate-400 font-mono">{{ $route->date }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                Planilla de Campo — {{ $route->zone->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Acopiador asignado: <strong class="text-slate-800">{{ $route->collector->name }}</strong> | 
                Estado: 
                @if($route->status === 'verificada')
                    <span class="uppercase font-bold text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <i class="fa-solid fa-check-double mr-1"></i> Verificada en Caudalímetro
                    </span>
                @elseif($route->status === 'descargada_planta')
                    <span class="uppercase font-bold text-xs px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-200">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> Cerrada • En Verificación por Planta
                    </span>
                @else
                    <span class="uppercase font-bold text-xs px-2.5 py-0.5 rounded-full bg-lime-100 text-[#0f1713] border border-lime-300">
                        <i class="fa-solid fa-truck-fast mr-1"></i> En Ruta (Campo)
                    </span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-4">
            <div class="bg-slate-50 border border-slate-200/80 px-5 py-3 rounded-2xl text-right">
                <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Total Acopiado Hoy</span>
                <span class="text-2xl font-black text-emerald-800 tracking-tight" id="headerTotalLiters">{{ number_format($route->total_collected_liters, 2) }} <span class="text-xs font-bold text-slate-500">L</span></span>
            </div>

            @if($route->status !== 'descargada_planta' && $route->status !== 'verificada')
            <form action="{{ route('acopio.discharge', $route->id) }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('¿Confirmas cerrar la ruta de hoy con {{ number_format($route->total_collected_liters, 2) }} L y enviar la leche a planta para verificación por caudalímetro?')"
                    class="bg-[#0f1713] hover:bg-black text-[#bef264] font-bold px-5 py-3 rounded-2xl shadow-sm text-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-flag-checkered text-sm"></i>
                    <span>Cerrar Ruta</span>
                </button>
            </form>
            @elseif($route->status === 'descargada_planta')
            <span class="bg-amber-50 text-amber-900 border border-amber-200 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-amber-600"></i>
                <span>Ruta Cerrada • Esperando Caudalímetro</span>
            </span>
            @else
            <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-check-double text-emerald-600"></i>
                <span>Verificada por Caudalímetro ({{ $route->reception ? $route->reception->flowmeter_liters : $route->total_collected_liters }} L)</span>
            </span>
            @endif
        </div>
    </div>

    <!-- Tabla Proveedores Spark Style con Buscador y Reordenamiento -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-black text-slate-900">Planilla de Proveedores de la Zona</h3>
                <p class="text-xs text-slate-500">Anota los litros de cada proveedor. Los proveedores registrados bajan automáticamente al final de la lista.</p>
            </div>

            @php
                $totalProducers = $route->zone->producers->count();
                $recordedCount = $route->records->count();
                $pendingCount = max(0, $totalProducers - $recordedCount);
            @endphp

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/60 px-3 py-1 rounded-full">
                    <i class="fa-solid fa-hourglass-half mr-1"></i> <span id="counterPending">{{ $pendingCount }}</span> pendientes
                </span>
                <span class="text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/60 px-3 py-1 rounded-full">
                    <i class="fa-solid fa-circle-check mr-1"></i> <span id="counterRecorded">{{ $recordedCount }}</span> acopiados
                </span>
                <span class="text-xs font-bold bg-slate-100 text-slate-600 px-3 py-1 rounded-full">
                    Total: {{ $totalProducers }}
                </span>
            </div>
        </div>

        <!-- Buscador en tiempo real para encontrar rápidamente al proveedor -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-sm"></i>
            </div>
            <input type="text" id="producerSearchInput" onkeyup="filterProducers()"
                placeholder="Buscar proveedor por nombre completo o número de DNI..."
                class="w-full pl-10 pr-10 py-3 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-800 focus:ring-2 focus:ring-spark-lime focus:border-transparent transition">
            <button type="button" id="clearSearchBtn" onclick="clearSearch()" class="hidden absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs" id="producersTable">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5 rounded-l-xl w-12">#</th>
                        <th class="p-3.5">Productor / Proveedor</th>
                        <th class="p-3.5">DNI</th>
                        <th class="p-3.5">Teléfono</th>
                        <th class="p-3.5">Litros Anotados</th>
                        <th class="p-3.5">Hora</th>
                        <th class="p-3.5">Observación</th>
                        <th class="p-3.5 rounded-r-xl text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="producersTableBody">
                    @forelse($route->zone->producers as $index => $producer)
                        @php
                            $record = $route->records->firstWhere('producer_id', $producer->id);
                            $isRecorded = $record !== null;
                        @endphp
                        <tr class="producer-row hover:bg-slate-50/80 transition {{ $isRecorded ? 'bg-slate-50/40 text-slate-600' : 'bg-white' }}"
                            data-search="{{ strtolower($producer->name . ' ' . ($producer->dni ?? '')) }}"
                            data-producer-id="{{ $producer->id }}"
                            data-recorded="{{ $isRecorded ? '1' : '0' }}">
                            <td class="p-3.5 text-slate-400 font-mono row-index">{{ $index + 1 }}</td>
                            <td class="p-3.5 font-bold text-slate-900 text-sm">
                                <div class="flex items-center gap-2">
                                    <span>{{ $producer->name }}</span>
                                    @if($isRecorded)
                                        <i class="fa-solid fa-circle-check text-emerald-500 text-xs" title="Entrega registrada"></i>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3.5 font-mono text-slate-600">{{ $producer->dni ?: '—' }}</td>
                            <td class="p-3.5 text-slate-600">{{ $producer->phone ?: '—' }}</td>
                            
                            <td class="p-3.5 font-black text-sm">
                                @if($record)
                                    <span class="text-emerald-700 bg-emerald-50 border border-emerald-200/70 px-2.5 py-1 rounded-xl">
                                        {{ number_format($record->liters, 2) }} L
                                    </span>
                                @else
                                    <span class="text-amber-800 bg-amber-50 border border-amber-200/70 text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                        <i class="fa-solid fa-clock text-[9px]"></i> Pendiente
                                    </span>
                                @endif
                            </td>

                            <td class="p-3.5 text-slate-500 font-mono">
                                {{ $record ? ($record->collected_at ?: '05:00 AM') : '—' }}
                            </td>

                            <td class="p-3.5 text-slate-500 italic max-w-xs truncate">
                                {{ $record && $record->notes ? $record->notes : '—' }}
                            </td>

                            <td class="p-3.5 text-right">
                                @if($route->status !== 'descargada_planta' && $route->status !== 'verificada')
                                    @if($isRecorded)
                                        <span class="text-xs font-bold text-emerald-700">Acopiado</span>
                                    @else
                                        <button type="button" 
                                            onclick="openRecordModal({{ $producer->id }}, '{{ addslashes($producer->name) }}', '{{ $producer->dni ?? '' }}', '', '')"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-extrabold bg-[#bef264] hover:bg-lime-400 text-[#0f1713] transition cursor-pointer shadow-sm">
                                            <i class="fa-solid fa-plus text-xs"></i>
                                            <span>Registrar</span>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400 font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-slate-300"></i> Cerrado
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">No hay productores asignados a esta zona todavía.</td>
                        </tr>
                    @endforelse
                    <tr id="noResultsRow" class="hidden">
                        <td colspan="8" class="p-8 text-center text-slate-400">
                            <i class="fa-solid fa-filter-circle-xmark text-2xl block mb-2 text-slate-300"></i>
                            No se encontraron proveedores que coincidan con la búsqueda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECCIÓN: Historial de Rutas y Observaciones de Planta -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 border-b border-slate-100 pb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] bg-slate-100 text-slate-700 font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        Trazabilidad & Mermas
                    </span>
                </div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-slate-500"></i>
                    Historial de Acopio por Rutas y Cuadre de Planta
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Historial de tus rutas, comparación de litros anotados en campo vs caudalímetro en planta, mermas registradas y observaciones del Jefe de Producción.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('acopio.historial') }}" class="text-xs font-bold text-slate-700 hover:text-black bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-2xl transition inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Ver Reporte Completo
                </a>
                <span class="text-xs font-bold text-slate-500 bg-slate-50 px-3 py-1.5 rounded-2xl border border-slate-200/60">
                    {{ $historicalRoutes->count() }} rutas registradas
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5 rounded-l-xl">Fecha</th>
                        <th class="p-3.5">Zona Asignada</th>
                        <th class="p-3.5">Litros Campo</th>
                        <th class="p-3.5">Caudalímetro Planta</th>
                        <th class="p-3.5">Diferencia / Merma</th>
                        <th class="p-3.5">Observaciones de Planta</th>
                        <th class="p-3.5">Estado</th>
                        <th class="p-3.5 rounded-r-xl text-center">Ver Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($historicalRoutes as $histRoute)
                        @php
                            $reception = $histRoute->reception;
                            $fieldLiters = (float) $histRoute->total_collected_liters;
                            $plantLiters = $reception ? (float) $reception->flowmeter_liters : null;
                            $diff = $plantLiters !== null ? ($plantLiters - $fieldLiters) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3.5 font-mono text-slate-700 font-bold">
                                {{ \Carbon\Carbon::parse($histRoute->date)->format('d/m/Y') }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-800">
                                {{ $histRoute->zone ? $histRoute->zone->name : 'Zona Huata' }}
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
                                @if($reception && $reception->observation)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-800 bg-slate-100/80 px-2.5 py-1 rounded-xl">
                                        <i class="fa-solid fa-comment-dots text-slate-500"></i>
                                        <span>{{ $reception->observation }}</span>
                                    </span>
                                @elseif($reception)
                                    <span class="text-emerald-700 font-medium inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-emerald-500 text-xs"></i> Todo conforme
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">En espera de verificación</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                @if($reception && $reception->verification_status === 'incompleto')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Incompleto
                                    </span>
                                @elseif($reception && $reception->verification_status === 'con_observacion')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-exclamation"></i> Con Observación
                                    </span>
                                @elseif($histRoute->status === 'verificada')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check-double"></i> Conforme
                                    </span>
                                @elseif($histRoute->status === 'descargada_planta')
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
                                        'date' => \Carbon\Carbon::parse($histRoute->date)->format('d/m/Y'),
                                        'zone' => $histRoute->zone ? $histRoute->zone->name : 'Zona Huata',
                                        'status' => $reception ? $reception->verification_status : $histRoute->status,
                                        'field_liters' => number_format($fieldLiters, 2),
                                        'plant_liters' => $plantLiters !== null ? number_format($plantLiters, 2) : 'Pendiente',
                                        'difference' => $diff !== null ? number_format($diff, 2) . ' L' : 'Pendiente',
                                        'observation' => ($reception && $reception->observation) ? $reception->observation : ($reception ? 'Todo conforme' : 'En espera de revisión en planta'),
                                        'verifier' => ($reception && $reception->verifier) ? $reception->verifier->name : 'Jefe de Producción',
                                        'records' => $histRoute->records->map(function($r) {
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
                                    title="Ver detalle de la ruta">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">No hay historial previo de rutas registradas para este acopiador.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: REGISTRAR / ACTUALIZAR LITROS -->
<div id="recordModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeRecordModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200">
            <!-- Header Modal -->
            <div class="bg-slate-900 px-6 py-5 text-white flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#bef264]/20 border border-[#bef264]/30 flex items-center justify-center text-[#bef264]">
                        <i class="fa-solid fa-bottle-droplet text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black tracking-tight" id="modalTitle">Registrar Entrega</h3>
                        <p class="text-[11px] text-slate-400">Ruta 4:30 AM • {{ $route->zone->name }}</p>
                    </div>
                </div>
                <button type="button" onclick="closeRecordModal()" class="text-slate-400 hover:text-white transition text-lg cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Formulario Modal -->
            <form action="{{ route('acopio.delivery', $route->id) }}" method="POST" id="recordForm" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="producer_id" id="modalProducerId">

                <!-- Info Productor -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">Productor Seleccionado</span>
                    <h4 class="text-sm font-black text-slate-900" id="modalProducerName">—</h4>
                    <p class="text-xs text-slate-500 font-mono mt-0.5" id="modalProducerDni">DNI: —</p>
                </div>

                <!-- Campo Litros -->
                <div>
                    <label for="modalLitersInput" class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Cantidad de Leche (Litros) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.1" min="0.1" name="liters" id="modalLitersInput" required
                            placeholder="Ej. 25.5"
                            class="w-full px-4 py-3 bg-slate-50 focus:bg-white border-2 border-slate-200 focus:border-spark-lime rounded-2xl text-xl font-black text-slate-900 focus:ring-0 transition">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-sm font-bold text-slate-400">
                            Litros (L)
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Registra con precisión los litros medidos en porongo de campo.</p>
                </div>

                <!-- Campo Observación Opcional -->
                <div>
                    <label for="modalNotesInput" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nota u Observación de Campo (Opcional)
                    </label>
                    <input type="text" name="notes" id="modalNotesInput" maxlength="255"
                        placeholder="Ej. Porongo de aluminio, acopio en tranquera..."
                        class="w-full px-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-200 focus:border-spark-lime rounded-xl text-xs text-slate-800 transition">
                </div>

                <!-- Botones Acción -->
                <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeRecordModal()"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" id="modalSubmitBtn"
                        class="px-6 py-2.5 rounded-xl text-xs font-black bg-[#0f1713] hover:bg-black text-[#bef264] transition shadow-md flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span id="modalSubmitText">Guardar Entrega</span>
                    </button>
                </div>
            </form>
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
    // 1. Buscador en tiempo real de proveedores
    function filterProducers() {
        const input = document.getElementById('producerSearchInput');
        const clearBtn = document.getElementById('clearSearchBtn');
        const query = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#producersTableBody tr.producer-row');
        const noResults = document.getElementById('noResultsRow');

        if (query.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }

        let visibleCount = 0;
        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            if (searchData.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount === 0 && rows.length > 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }

    function clearSearch() {
        const input = document.getElementById('producerSearchInput');
        input.value = '';
        filterProducers();
        input.focus();
    }

    // 2. Modal de Registro / Actualización de Litros
    function openRecordModal(producerId, producerName, producerDni, currentLiters, currentNotes) {
        document.getElementById('modalProducerId').value = producerId;
        document.getElementById('modalProducerName').textContent = producerName;
        document.getElementById('modalProducerDni').textContent = producerDni ? ('DNI: ' + producerDni) : 'DNI: No registrado';
        
        const litersInput = document.getElementById('modalLitersInput');
        const notesInput = document.getElementById('modalNotesInput');
        const modalTitle = document.getElementById('modalTitle');
        const submitText = document.getElementById('modalSubmitText');

        if (currentLiters && parseFloat(currentLiters) > 0) {
            modalTitle.textContent = 'Actualizar Entrega';
            submitText.textContent = 'Actualizar Entrega';
            litersInput.value = currentLiters;
            notesInput.value = currentNotes || '';
        } else {
            modalTitle.textContent = 'Registrar Entrega';
            submitText.textContent = 'Guardar Entrega';
            litersInput.value = '';
            notesInput.value = '';
        }

        document.getElementById('recordModal').classList.remove('hidden');
        setTimeout(() => {
            litersInput.focus();
            litersInput.select();
        }, 100);
    }

    function closeRecordModal() {
        document.getElementById('recordModal').classList.add('hidden');
    }

    document.getElementById('recordForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const button = document.getElementById('modalSubmitBtn');
        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok) {
                alert(result.message || Object.values(result.errors || {}).flat().join('\n') || 'No se pudo registrar la entrega.');
                return;
            }

            const row = [...document.querySelectorAll('.producer-row')].find(item => item.dataset.producerId === String(result.record.producer_id));
            const cells = row.querySelectorAll('td');
            cells[4].textContent = Number(result.record.liters).toFixed(2) + ' L';
            cells[5].textContent = result.record.collected_at;
            cells[6].textContent = result.record.notes || '—';
            cells[7].textContent = 'Acopiado';
            row.dataset.recorded = '1';
            document.getElementById('producersTableBody').appendChild(row);
            document.getElementById('counterPending').textContent = Number(document.getElementById('counterPending').textContent) - 1;
            document.getElementById('counterRecorded').textContent = Number(document.getElementById('counterRecorded').textContent) + 1;
            document.getElementById('headerTotalLiters').textContent = Number(result.total).toFixed(2) + ' L';
            closeRecordModal();
        } catch (error) {
            alert('No se pudo conectar con el servidor.');
        } finally {
            button.disabled = false;
        }
    });

    // 3. Modal de Historial de Ruta (Ojito)
    function openHistoryModal(data) {
        document.getElementById('histHeaderSubtitle').textContent = data.zone + ' • ' + data.date;
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

    // Tecla ESC para cerrar modales
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeRecordModal();
            closeHistoryModal();
        }
    });
</script>
@endsection
