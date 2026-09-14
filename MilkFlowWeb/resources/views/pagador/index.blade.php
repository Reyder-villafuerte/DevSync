@extends('layouts.app')

@section('title', 'Planilla de Sobres y Pagos en Ruta (Viernes)')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Pagos de Sueldos en Ruta (Viernes)</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Efectivo en Mano</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    <strong>Ciclo Semanal de Huata:</strong> Miércoles (Cierre de caja) · Jueves (Conteo de sobres) · Viernes (Pago en ruta junto al acopiador).
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('pagos.ruta.history') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition flex items-center gap-1.5">
                <i class="fa-solid fa-receipt"></i> Ver Historial de Pagos
            </a>
        </div>
    </div>

    <!-- Tarjetas de Métricas de Sobres de la Ruta -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total en Sobres (Ruta)</span>
                <div class="text-2xl font-black text-slate-900 mt-1">S/ {{ number_format($montoTotalEfectivo, 2) }}</div>
                <span class="text-xs text-slate-500 font-medium mt-0.5 block">{{ $totalSobres }} sobres preparados</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-envelopes-bulk"></i>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-emerald-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Sobres Entregados Hoy</span>
                <div class="text-2xl font-black text-emerald-700 mt-1">S/ {{ number_format($montoEntregado, 2) }}</div>
                <span class="text-xs text-emerald-600 font-medium mt-0.5 block">{{ $sobresEntregados }} productores pagados</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-amber-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Efectivo en Custodia (Camioneta)</span>
                <div class="text-2xl font-black text-amber-700 mt-1">S/ {{ number_format($montoRemanente, 2) }}</div>
                <span class="text-xs text-amber-600 font-medium mt-0.5 block">{{ $sobresPendientes }} sobres pendientes</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-vault"></i>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros por Zona y Búsqueda -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <form method="GET" action="{{ route('pagos.ruta.index') }}" class="space-y-4">
            <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <!-- Selector de Zonas -->
                <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
                    <span class="text-xs font-bold text-slate-700 mr-2 flex items-center gap-1">
                        <i class="fa-solid fa-location-dot text-[#0f1713]"></i> Zona de Ruta:
                    </span>
                    <a href="{{ route('pagos.ruta.index', array_merge(request()->except('zone_id'), [])) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ !$selectedZoneId ? 'bg-[#0f1713] text-[#bef264]' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Todas las Zonas
                    </a>
                    @foreach($zones as $z)
                        <a href="{{ route('pagos.ruta.index', array_merge(request()->except('zone_id'), ['zone_id' => $z->id])) }}" 
                           class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $selectedZoneId == $z->id ? 'bg-[#0f1713] text-[#bef264]' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $z->name }}
                        </a>
                    @endforeach
                </div>

                <!-- Filtro de estado -->
                <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl text-xs font-bold w-full md:w-auto">
                    <a href="{{ route('pagos.ruta.index', array_merge(request()->except('status'), ['status' => 'todos'])) }}" 
                       class="px-3 py-1.5 rounded-xl transition {{ $selectedStatus === 'todos' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                        Todos
                    </a>
                    <a href="{{ route('pagos.ruta.index', array_merge(request()->except('status'), ['status' => 'autorizados'])) }}" 
                       class="px-3 py-1.5 rounded-xl transition {{ $selectedStatus === 'autorizados' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                        Listos en Sobre ({{ $sobresPendientes }})
                    </a>
                    <a href="{{ route('pagos.ruta.index', array_merge(request()->except('status'), ['status' => 'pendientes'])) }}" 
                       class="px-3 py-1.5 rounded-xl transition {{ $selectedStatus === 'pendientes' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 hover:text-amber-700' }}">
                        Sin Autorizar
                    </a>
                    <a href="{{ route('pagos.ruta.index', array_merge(request()->except('status'), ['status' => 'pagados'])) }}" 
                       class="px-3 py-1.5 rounded-xl transition {{ $selectedStatus === 'pagados' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-500 hover:text-emerald-700' }}">
                        Pagados ({{ $sobresEntregados }})
                    </a>
                </div>
            </div>

            <div class="flex gap-2">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar productor por nombre o DNI..." class="w-full text-xs pl-9 pr-3 py-2.5 rounded-2xl border border-slate-200 bg-slate-50/70 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-[#0f1713] text-[#bef264] text-xs font-bold rounded-2xl hover:bg-slate-900 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-filter text-[10px]"></i> Filtrar
                </button>
                @if($search || $selectedZoneId || $selectedStatus !== 'todos')
                    <a href="{{ route('pagos.ruta.index') }}" class="px-3 py-2.5 bg-slate-100 text-slate-600 text-xs font-bold rounded-2xl hover:bg-slate-200 transition flex items-center justify-center">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Planilla de Sobres para Entrega en Efectivo -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Planilla de Productores — Entrega en Ruta</h3>
            </div>
            <span class="text-xs text-slate-400 font-medium">Mostrando {{ count($envelopes) }} productores</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Productor</th>
                        <th class="pb-3 px-3">Zona</th>
                        <th class="pb-3 px-3">Leche Acopiada</th>
                        <th class="pb-3 px-3">Subtotal Bruto</th>
                        <th class="pb-3 px-3">Deducciones</th>
                        <th class="pb-3 px-3 text-right">Efectivo en Sobre</th>
                        <th class="pb-3 px-3 text-center">Estado</th>
                        <th class="pb-3 px-3 text-right">Acción en Campo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($envelopes as $item)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3">
                            <div class="font-bold text-slate-900 text-xs">{{ $item['producer']->name }}</div>
                            <div class="text-[10px] text-slate-400">DNI: {{ $item['producer']->dni ?: '—' }} · Tel: {{ $item['producer']->phone ?: '—' }}</div>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                {{ $item['producer']->zone ? $item['producer']->zone->name : 'Sin zona' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 font-semibold text-slate-700">
                            {{ number_format($item['liters'], 2) }} L
                        </td>
                        <td class="py-3.5 px-3 text-slate-600">
                            @if($item['is_authorized'])
                                S/ {{ number_format($item['gross'], 2) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3">
                            @if($item['is_authorized'])
                                @if($item['deductions'] > 0)
                                    <span class="text-rose-600 font-bold">-S/ {{ number_format($item['deductions'], 2) }}</span>
                                @else
                                    <span class="text-slate-400">S/ 0.00</span>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            @if($item['is_authorized'])
                                <span class="text-sm font-black text-slate-900 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-xl text-emerald-800">
                                    S/ {{ number_format($item['net'], 2) }}
                                </span>
                            @else
                                <span class="text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-xl inline-block" title="El efectivo no figura hasta que el Administrador autorice el pago">
                                    — Sin Autorizar
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            @if($item['status'] === 'pagado')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <i class="fa-solid fa-circle-check text-[9px]"></i> Sobre Entregado
                                </span>
                            @elseif($item['status'] === 'autorizado')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]">
                                    <i class="fa-solid fa-envelope-circle-check text-[9px]"></i> Listo en Sobre
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Esperando autorización previa del Administrador">
                                    <i class="fa-solid fa-lock text-[9px]"></i> No Autorizado
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            @if($item['status'] === 'pagado')
                                <a href="{{ route('pagos.ruta.receipt', $item['settlement']->id) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-[11px] font-bold transition inline-flex items-center gap-1 shadow-sm">
                                    <i class="fa-solid fa-print"></i> Ver Recibo
                                </a>
                            @elseif($item['can_deliver'])
                                <form action="{{ route('pagos.ruta.pay', $item['producer']->id) }}" method="POST" onsubmit="return confirm('¿Confirmar la entrega del sobre con S/ {{ number_format($item['net'], 2) }} en efectivo a {{ addslashes($item['producer']->name) }}?');" class="inline">
                                    @csrf
                                    <input type="hidden" name="zone_id" value="{{ $selectedZoneId }}">
                                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                                    <input type="hidden" name="search" value="{{ $search }}">
                                    <button type="submit" class="px-3.5 py-1.5 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] rounded-xl text-[11px] font-black transition inline-flex items-center gap-1.5 shadow-sm">
                                        <i class="fa-solid fa-hand-holding-dollar"></i> Entregar Sobre
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled class="px-3 py-1.5 bg-slate-100 text-slate-400 rounded-xl text-[11px] font-semibold inline-flex items-center gap-1 cursor-not-allowed border border-slate-200/80" title="Requiere que el Administrador autorice el pago previamente">
                                    <i class="fa-solid fa-lock text-[10px]"></i> Sin Autorizar
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty

                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400 font-medium">
                            <i class="fa-solid fa-envelope-open text-2xl text-slate-300 block mb-2"></i>
                            No se encontraron productores con sobres para los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
