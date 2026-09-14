@extends('layouts.app')

@section('title', 'Autorización de Pagos a Proveedores')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-money-check-dollar"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Panel de Autorización de Pagos a Proveedores</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">
                        Liquidaciones Huata
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Revisa las entregas semanales, desglose por día, compras de queso y penalizaciones por agua en Lactoscan para autorizar desembolsos.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.precios.index') }}" class="px-3.5 py-2 rounded-2xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
                <i class="fa-solid fa-tags text-emerald-600"></i> Ajustar Tarifas
            </a>

            @if(count($pendientesList) > 0)
            <form action="{{ route('admin.pagos.authorize-all') }}" method="POST" onsubmit="return confirm('¿Confirmas autorizar y liquidar el pago de TODOS los productores pendientes? Todos los acumuladores semanales se reiniciarán.')">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-2xl bg-[#0f1713] text-[#bef264] text-xs font-bold hover:bg-slate-900 transition inline-flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-bolt"></i> Autorizar Todos ({{ count($pendientesList) }})
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- TARJETAS DE RESUMEN METRICO -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Productores Pendientes -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Productores por Liquidar</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-[#0f1713]">{{ count($pendientesList) }}</span>
                <span class="text-xs font-bold text-slate-400">proveedores</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Con entregas o deducciones activas.</p>
        </div>

        <!-- Litros por Pagar -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Litros Pendientes</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-[#0f1713]">{{ number_format($totalLitrosPendientes, 1) }}</span>
                <span class="text-xs font-bold text-slate-400">Litros</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Acopio acumulado en el ciclo abierto.</p>
        </div>

        <!-- Deducciones / Quesos y Calidad -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-rose-600 tracking-wider">Deducciones a Aplicar</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-rose-600">S/ {{ number_format($totalDeduccionesPendientes, 2) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Compras de queso a cuenta y penalidades por calidad.</p>
        </div>

        <!-- Total Neto a Desembolsar -->
        <div class="bg-[#0f1713] text-white rounded-3xl p-6 shadow-md flex flex-col justify-between">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264] text-[#0f1713] uppercase tracking-wider">Desembolso Neto</span>
                <div class="flex items-baseline gap-1.5 mt-2">
                    <span class="text-3xl font-black text-[#bef264]">S/ {{ number_format($totalNetoPendiente, 2) }}</span>
                </div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">Pago directo en efectivo en planta de Huata.</p>
        </div>
    </div>

    <!-- TABLA DE PRODUCTORES PENDIENTES DE PAGO -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-black text-slate-900">Planilla de Liquidación Semanal</h3>
                <p class="text-xs text-slate-500">Haz clic en el ícono de ojo para desplegar el desglose diario (Jueves, Viernes, etc.) y deducciones de cada productor.</p>
            </div>
            <span class="text-xs text-slate-400 font-medium">Ciclo hasta hoy: {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Productor / Zona</th>
                        <th class="pb-3 px-3">Período</th>
                        <th class="pb-3 px-3">Litros</th>
                        <th class="pb-3 px-3">Tarifa Base / Calidad</th>
                        <th class="pb-3 px-3">Subtotal Bruto</th>
                        <th class="pb-3 px-3">Deducciones (Quesos / Calidad)</th>
                        <th class="pb-3 px-3">Neto a Liquidar</th>
                        <th class="pb-3 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pendientesList as $item)
                    <!-- Fila Principal de Resumen -->
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- Productor -->
                        <td class="py-3.5 px-3">
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        onclick="toggleDetail('row-detail-{{ $item->producer->id }}')" 
                                        class="w-7 h-7 rounded-xl bg-slate-100 hover:bg-[#bef264] text-slate-600 hover:text-[#0f1713] transition inline-flex items-center justify-center font-bold shadow-xs cursor-pointer"
                                        title="Ver detalles despejados">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                                <div>
                                    <span class="font-bold text-slate-900 block text-xs">{{ $item->producer->name }}</span>
                                    <span class="text-[10px] text-slate-500">DNI: {{ $item->producer->dni ?: '—' }} • {{ $item->producer->zone ? $item->producer->zone->name : 'Sin Zona' }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Período -->
                        <td class="py-3.5 px-3 whitespace-nowrap text-slate-600 font-medium">
                            {{ \Carbon\Carbon::parse($item->start_date)->format('d/m') }} al {{ \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') }}
                        </td>

                        <!-- Litros -->
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <span class="text-sm font-black text-slate-900">{{ number_format($item->liters, 2) }}</span>
                            <span class="text-[10px] text-slate-400 font-bold">L</span>
                        </td>

                        <!-- Tarifa Base y Calidad -->
                        <td class="py-3.5 px-3">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-[#0f1713]">
                                    S/ {{ number_format($item->effective_price, 2) }} / L
                                </span>
                                @if($item->price_info['penalty_type'] === 'grave_expulsion')
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300 w-fit">
                                        Agua > 5% (Expulsión)
                                    </span>
                                @elseif($item->price_info['penalty_type'] === 'leve_descuento')
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-amber-100 text-amber-900 border border-amber-300 w-fit">
                                        Agua &le; 5% (-S/ {{ number_format($item->base_price - $item->effective_price, 2) }}/L)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 w-fit">
                                        Leche Conforme
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Subtotal Bruto Base -->
                        <td class="py-3.5 px-3 font-semibold text-slate-700 whitespace-nowrap">
                            S/ {{ number_format($item->gross_base, 2) }}
                        </td>

                        <!-- Deducciones con desglose -->
                        <td class="py-3.5 px-3">
                            @if($item->total_deductions > 0)
                                <div class="space-y-0.5">
                                    <span class="font-bold text-rose-600 block text-xs">- S/ {{ number_format($item->total_deductions, 2) }}</span>
                                    @if($item->adulteration_found)
                                    <span class="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 block truncate max-w-xs font-semibold">
                                        • Penalidad agua ({{ $item->adulteration_details->water_percentage }}% el {{ $item->adulteration_details->day_name }}): -S/ {{ number_format($item->water_penalty_total, 2) }}
                                    </span>
                                    @endif
                                    @if($item->cheese_deductions_total > 0)
                                    <span class="text-[10px] text-slate-600 block truncate max-w-xs">
                                        • Compras de queso: -S/ {{ number_format($item->cheese_deductions_total, 2) }}
                                    </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400 text-xs">— Sin deducciones</span>
                            @endif
                        </td>

                        <!-- Neto a Liquidar -->
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <span class="text-sm font-black text-emerald-900 bg-emerald-50 px-2.5 py-1 rounded-xl border border-emerald-200">
                                S/ {{ number_format($item->net, 2) }}
                            </span>
                        </td>

                        <!-- Acciones -->
                        <td class="py-3.5 px-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <button type="button" 
                                        onclick="toggleDetail('row-detail-{{ $item->producer->id }}')" 
                                        class="px-2.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-[#bef264] text-slate-700 hover:text-[#0f1713] transition inline-flex items-center gap-1.5"
                                        title="Ver detalles despejados">
                                    <i class="fa-solid fa-eye text-[11px]"></i>
                                    <span>Ver</span>
                                </button>

                                <form action="{{ route('admin.pagos.authorize-single', $item->producer->id) }}" method="POST" onsubmit="return confirm('¿Autorizar y liquidar el pago de S/ {{ number_format($item->net, 2) }} a {{ $item->producer->name }}?')">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 rounded-xl text-xs font-bold bg-[#0f1713] hover:bg-slate-900 text-[#bef264] transition shadow-sm inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-money-bill-wave text-[11px]"></i>
                                        <span>Autorizar</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- FILA DESPLEGABLE CON DETALLES DESPEJADOS -->
                    <tr id="row-detail-{{ $item->producer->id }}" class="hidden bg-slate-50/70 border-b border-slate-200">
                        <td colspan="8" class="p-5">
                            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-5">
                                <!-- Cabecera del desglose -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-receipt"></i>
                                        </span>
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900">
                                                Desglose Detallado de Liquidación — {{ $item->producer->name }}
                                            </h4>
                                            <p class="text-[11px] text-slate-500">
                                                Ciclo Semanal: {{ \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') }}
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            onclick="toggleDetail('row-detail-{{ $item->producer->id }}')" 
                                            class="text-xs text-slate-400 hover:text-slate-700 font-bold self-start sm:self-auto cursor-pointer">
                                        <i class="fa-solid fa-xmark mr-1"></i> Cerrar Desglose
                                    </button>
                                </div>

                                <!-- SECCIÓN 1: LITROS DÍA POR DÍA -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                            <i class="fa-solid fa-calendar-week text-emerald-600"></i> Entregas Diarias de Leche (Acopio por Día)
                                        </span>
                                        <span class="text-xs font-black text-[#0f1713] bg-[#bef264]/40 px-2.5 py-0.5 rounded-full border border-[#bef264]">
                                            Total: {{ number_format($item->liters, 2) }} Litros
                                        </span>
                                    </div>

                                    @if(count($item->daily_breakdown) > 0)
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
                                        @foreach($item->daily_breakdown as $rec)
                                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-200/70 hover:border-emerald-300 transition">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-black text-slate-800 uppercase">{{ $rec->day_name }}</span>
                                                <span class="text-[9px] text-slate-400 font-mono">{{ $rec->date_formatted }}</span>
                                            </div>
                                            <div class="mt-2 text-center">
                                                <span class="text-lg font-black text-[#0f1713]">{{ number_format($rec->liters, 2) }}</span>
                                                <span class="text-[10px] text-slate-400 font-bold">L</span>
                                            </div>
                                            <div class="mt-1.5 pt-1.5 border-t border-slate-200/50 text-[9px] text-slate-500 space-y-0.5">
                                                <div class="truncate"><i class="fa-solid fa-truck text-[8px] text-slate-400 mr-1"></i>{{ $rec->collector }}</div>
                                                <div class="truncate"><i class="fa-solid fa-location-dot text-[8px] text-slate-400 mr-1"></i>{{ $rec->zone }}</div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <p class="text-xs text-slate-400 italic">No se registraron entregas en este ciclo.</p>
                                    @endif
                                </div>

                                <!-- SECCIÓN 2: DESGLOSE DE DESCUENTOS (QUESOS Y CALIDAD) -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                    <!-- A: Compras de Queso -->
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/70 space-y-2.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                                <i class="fa-solid fa-cheese text-amber-500"></i> Compras de Queso a Cuenta de Leche
                                            </span>
                                            <span class="text-xs font-bold text-rose-600">
                                                - S/ {{ number_format($item->cheese_deductions_total, 2) }}
                                            </span>
                                        </div>

                                        @if($item->cheese_deductions->count() > 0)
                                        <div class="space-y-1.5">
                                            @foreach($item->cheese_deductions as $cd)
                                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200/50">
                                                <div>
                                                    <span class="font-bold text-slate-800 block">{{ $cd->concept }}</span>
                                                    <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($cd->date)->format('d/m/Y') }} • Cargo directo a liquidación</span>
                                                </div>
                                                <span class="font-black text-rose-600">- S/ {{ number_format($cd->amount, 2) }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <p class="text-xs text-slate-400 italic py-2">
                                            El productor no realizó compras de queso con cargo a leche en este ciclo semanal.
                                        </p>
                                        @endif
                                    </div>

                                    <!-- B: Penalidad por Leche Adulterada (Regla Semanal Huata) -->
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/70 space-y-2.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                                <i class="fa-solid fa-vial-circle-check text-indigo-600"></i> Calidad Lactoscan & Penalidad Semanal
                                            </span>
                                            <span class="text-xs font-bold {{ $item->water_penalty_total > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                {{ $item->water_penalty_total > 0 ? '- S/ ' . number_format($item->water_penalty_total, 2) : 'S/ 0.00' }}
                                            </span>
                                        </div>

                                        @if($item->adulteration_found)
                                        <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs space-y-1.5">
                                            <div class="flex items-center gap-2 text-rose-800 font-bold">
                                                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                                                <span>Adulteración detectada el {{ $item->adulteration_details->day_name }} {{ $item->adulteration_details->date_formatted }}</span>
                                            </div>
                                            <p class="text-[11px] text-rose-700 leading-relaxed">
                                                El análisis Lactoscan arrojó <strong>{{ $item->adulteration_details->water_percentage }}% de agua añadida</strong>.
                                            </p>
                                            <div class="p-2 bg-white/80 rounded-lg text-[10px] text-rose-900 space-y-0.5 border border-rose-200/60 font-medium">
                                                <div><strong>📌 Regla Distrital de Huata:</strong> Si se detecta leche adulterada en cualquier día del rango, la penalidad se descuenta a <strong>TODA LA SEMANA ({{ number_format($item->liters, 2) }} L)</strong>.</div>
                                                <div>• Tarifa base vigente: S/ {{ number_format($item->base_price, 2) }} / L</div>
                                                <div>• Tarifa penalizada aplicada: <strong>S/ {{ number_format($item->effective_price, 2) }} / L</strong> (-S/ {{ number_format($item->adulteration_details->penalty_per_liter, 2) }}/L)</div>
                                                <div class="text-rose-700 font-bold">• Descuento semanal: {{ number_format($item->liters, 2) }} L × S/ {{ number_format($item->adulteration_details->penalty_per_liter, 2) }} = <strong>- S/ {{ number_format($item->water_penalty_total, 2) }}</strong></div>
                                            </div>
                                        </div>
                                        @else
                                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs flex items-start gap-2 text-emerald-800">
                                            <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
                                            <div>
                                                <span class="font-bold block">Leche 100% Conforme y Pura</span>
                                                <p class="text-[11px] text-emerald-700 mt-0.5 leading-relaxed">
                                                    Sin adulteración de agua (0.0%). Se liquida la totalidad de los <strong>{{ number_format($item->liters, 2) }} litros</strong> a la tarifa plena de <strong>S/ {{ number_format($item->base_price, 2) }} / L</strong> sin penalizaciones.
                                                </p>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- SECCIÓN 3: BALANCE FINAL DE LIQUIDACIÓN -->
                                <div class="bg-[#0f1713] text-white rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div class="flex flex-wrap items-center gap-4 text-xs">
                                        <div>
                                            <span class="text-[10px] text-slate-400 block uppercase">Subtotal Base</span>
                                            <span class="font-bold text-white">S/ {{ number_format($item->gross_base, 2) }}</span>
                                            <span class="text-[9px] text-slate-400">({{ number_format($item->liters, 2) }} L × S/ {{ number_format($item->base_price, 2) }})</span>
                                        </div>
                                        @if($item->water_penalty_total > 0)
                                        <div class="border-l border-white/10 pl-4">
                                            <span class="text-[10px] text-rose-400 block uppercase">Penalidad Agua</span>
                                            <span class="font-bold text-rose-400">- S/ {{ number_format($item->water_penalty_total, 2) }}</span>
                                            <span class="text-[9px] text-slate-400">(Toda la semana)</span>
                                        </div>
                                        @endif
                                        @if($item->cheese_deductions_total > 0)
                                        <div class="border-l border-white/10 pl-4">
                                            <span class="text-[10px] text-amber-300 block uppercase">Deducción Queso</span>
                                            <span class="font-bold text-amber-300">- S/ {{ number_format($item->cheese_deductions_total, 2) }}</span>
                                        </div>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-3 self-end sm:self-auto">
                                        <div class="text-right">
                                            <span class="text-[10px] text-[#bef264] uppercase font-bold block">Neto a Desembolsar</span>
                                            <span class="text-xl font-black text-[#bef264]">S/ {{ number_format($item->net, 2) }}</span>
                                        </div>
                                        <form action="{{ route('admin.pagos.authorize-single', $item->producer->id) }}" method="POST" onsubmit="return confirm('¿Confirmas autorizar y liquidar el pago de S/ {{ number_format($item->net, 2) }} a {{ $item->producer->name }}?')">
                                            @csrf
                                            <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#bef264] text-[#0f1713] text-xs font-black hover:bg-lime-400 transition shadow-sm inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-check"></i> Autorizar Ahora
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400 font-medium">
                            <i class="fa-solid fa-circle-check text-2xl text-emerald-500 block mb-2"></i>
                            No hay pagos semanales pendientes. Todos los proveedores están al día.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleDetail(id) {
        const row = document.getElementById(id);
        if (row) {
            row.classList.toggle('hidden');
        }
    }
</script>
@endpush
