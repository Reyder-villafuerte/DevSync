@extends('layouts.app')

@section('title', 'Acopio de Leche - Proveedor')

@section('content')
<div class="space-y-6">
    <!-- Header Banner con Fecha de Hoy -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-bucket"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Panel de Acopio de Leche</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Proveedor Huata</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                    <span><i class="fa-regular fa-calendar-check text-emerald-600 mr-1"></i> {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                    <span>•</span>
                    <span>Zona Asignada: <strong>{{ $user->zone ? $user->zone->name : 'Sin Zona Asignada' }}</strong></span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('productor.zonas') }}" class="px-3.5 py-2 rounded-2xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
                <i class="fa-solid fa-arrows-split-up-and-left text-amber-600"></i> Solicitar Rotación
            </a>
            <a href="{{ route('productor.pagos') }}" class="px-3.5 py-2 rounded-2xl bg-[#0f1713] text-[#bef264] text-xs font-bold hover:bg-slate-900 transition inline-flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-money-bill-wave"></i> Ver Liquidaciones
            </a>
        </div>
    </div>

    <!-- TARJETAS PRINCIPALES: ENTREGA DE HOY + ACUMULADO SEMANAL + PAGO SEMANAL -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- CARD 1: ENTREGA DE HOY Y ABAJO SUMA SEMANAL ACUMULADA -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between relative overflow-hidden">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Entrega de Hoy</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $litrosHoy > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                        {{ $litrosHoy > 0 ? 'Registrado en Ruta' : 'Pendiente de Ruta' }}
                    </span>
                </div>
                
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-3xl font-black text-slate-900">{{ number_format($litrosHoy, 2) }}</span>
                    <span class="text-sm font-bold text-slate-500">Litros</span>
                </div>

                @if($entregaHoy)
                    <p class="text-[11px] text-slate-500 mt-1">
                        <i class="fa-solid fa-truck-pickup text-emerald-600 mr-1"></i> Recogido a las {{ substr($entregaHoy->collected_at ?? '05:00', 0, 5) }} por {{ $entregaHoy->route->collector->name ?? 'Acopiador' }}
                    </p>
                @else
                    <p class="text-[11px] text-slate-400 mt-1">
                        <i class="fa-regular fa-clock text-amber-500 mr-1"></i> El acopiador pasa en la ruta matutina de 4:30 AM
                    </p>
                @endif
            </div>

            <!-- SECCIÓN ABAJO: SUMA ACUMULADA DE LA SEMANA HASTA QUE SE CIERRE PAGO -->
            <div class="mt-6 pt-4 border-t border-slate-100 bg-slate-50/50 -mx-6 -mb-6 p-6 rounded-b-3xl">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Acumulado Semana Activa</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-2xl font-black text-[#0f1713]">{{ number_format($litrosSemana, 2) }}</span>
                            <span class="text-xs font-bold text-slate-500">Litros totales</span>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-2xl bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                </div>
                <p class="text-[10px] text-slate-400 mt-2 flex items-center gap-1">
                    <i class="fa-solid fa-rotate text-slate-400"></i> Ciclo abierto desde {{ $inicioCicloActivo->format('d/m/Y') }} hasta cierre de liquidación
                </p>
            </div>
        </div>

        <!-- CARD 2: PAGO SUMATORIO DE TODA LA SEMANA -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pago Sumatorio Semanal</span>
                    @if(isset($priceInfo) && $priceInfo['penalty_type'] === 'grave_expulsion')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Penalidad Agua > 5%: S/ {{ number_format($precioLitro, 2) }} / L
                        </span>
                    @elseif(isset($priceInfo) && $priceInfo['penalty_type'] === 'leve_descuento')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                            <i class="fa-solid fa-droplet-slash mr-1"></i> Descuento Agua &le; 5%: S/ {{ number_format($precioLitro, 2) }} / L
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50">
                            Tarifa S/ {{ number_format($precioLitro, 2) }} / L
                        </span>
                    @endif
                </div>

                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-3xl font-black text-slate-900">S/ {{ number_format($pagoNetoSemana, 2) }}</span>
                    <span class="text-xs font-bold text-slate-400">Neto estimado</span>
                </div>

                <div class="mt-3 space-y-1 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Total Bruto ({{ number_format($litrosSemana, 1) }} L):</span>
                        <span class="font-bold text-slate-700">S/ {{ number_format($pagoBrutoSemana, 2) }}</span>
                    </div>
                    @if($descuentosPendientes > 0)
                    <div class="flex justify-between text-rose-600 font-semibold">
                        <span>Descuentos pendientes:</span>
                        <span>- S/ {{ number_format($descuentosPendientes, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-slate-500">Estado de Liquidación:</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                        En curso (Pendiente)
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">
                    * Una vez liquidado y cambiado a estado <strong>Pagado</strong>, este monto se archiva en tu Historial de Pagos y el acumulador semanal se reinicia automáticamente desde cero.
                </p>
            </div>
        </div>

        <!-- CARD 3: ACCESO DIRECTO Y GESTIÓN DE ROTACIÓN / CALIDAD -->
        <div class="bg-[#0f1713] text-white rounded-3xl p-6 shadow-md flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -right-4 -bottom-6 text-white/5 text-9xl pointer-events-none">
                <i class="fa-solid fa-cow"></i>
            </div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264] text-[#0f1713] uppercase tracking-wider">
                        Tu Establo & Calidad
                    </span>
                    <i class="fa-solid fa-asterisk text-[#bef264] text-base"></i>
                </div>
                <h3 class="text-base font-black text-white tracking-tight">Zona {{ $user->zone ? $user->zone->name : 'Huata' }}</h3>
                <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                    Entrega tu leche en recipientes limpios antes de las 4:30 AM para mantener óptimos niveles de acidez y grasa.
                </p>
            </div>

            <div class="mt-6 pt-4 border-t border-white/10 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Total histórico entregado:</span>
                    <span class="font-bold text-[#bef264]">{{ number_format($totalLitrosHistorico, 1) }} L</span>
                </div>
                <a href="{{ route('productor.calidad') }}" class="w-full mt-2 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-vial-circle-check text-[#bef264]"></i> Ver Mi Reporte de Calidad
                </a>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE CONSULTA Y FILTROS TEMPORALES: DÍA / SEMANA / MES / RANGO -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <h3 class="text-base font-black text-slate-900">Registro de Acopio y Entregas</h3>
                <p class="text-xs text-slate-500">Consulta el detalle de tus entregas agrupadas por día, por semana o por mes.</p>
            </div>

            <!-- SELECTOR DE VISTA: DÍA, SEMANA, MES -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('productor.acopio', array_merge(request()->except(['page']), ['filtro' => 'dia'])) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $filtro === 'dia' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    <i class="fa-solid fa-calendar-day mr-1"></i> Por Día
                </a>
                <a href="{{ route('productor.acopio', array_merge(request()->except(['page']), ['filtro' => 'semana'])) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $filtro === 'semana' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    <i class="fa-solid fa-calendar-week mr-1"></i> Por Semana
                </a>
                <a href="{{ route('productor.acopio', array_merge(request()->except(['page']), ['filtro' => 'mes'])) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $filtro === 'mes' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    <i class="fa-solid fa-calendar mr-1"></i> Por Mes
                </a>
            </div>
        </div>

        <!-- FORMULARIO DE FILTRADO POR RANGO DE FECHAS -->
        <form action="{{ route('productor.acopio') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-3 bg-slate-50/60 p-4 rounded-2xl border border-slate-200/60 items-end">
            <input type="hidden" name="filtro" value="{{ $filtro }}">
            <div>
                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Fecha Desde</label>
                <input type="date" name="desde" value="{{ $fechaDesde }}" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Fecha Hasta</label>
                <input type="date" name="hasta" value="{{ $fechaHasta }}" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="w-full bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold py-2.5 rounded-xl text-xs transition shadow-sm">
                    <i class="fa-solid fa-filter mr-1"></i> Filtrar
                </button>
                @if($fechaDesde || $fechaHasta)
                <a href="{{ route('productor.acopio', ['filtro' => $filtro]) }}" class="px-3 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition flex items-center">
                    <i class="fa-solid fa-xmark"></i>
                </a>
                @endif
            </div>
        </form>

        <!-- TABLA SEGÚN EL FILTRO SELECCIONADO -->

        @if($filtro === 'dia')
        <!-- 1. VISTA POR DÍA -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Fecha</th>
                        <th class="pb-3 px-3">Hora de Recojo</th>
                        <th class="pb-3 px-3">Zona</th>
                        <th class="pb-3 px-3">Acopiador Responsable</th>
                        <th class="pb-3 px-3">Litros Entregados</th>
                        <th class="pb-3 px-3">Importe Estimado</th>
                        <th class="pb-3 px-3 text-right">Planta Caudalímetro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($registrosDiarios as $reg)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 font-bold text-slate-900">
                            {{ \Carbon\Carbon::parse($reg->route->date)->format('d/m/Y') }}
                            <span class="block text-[10px] font-normal text-slate-400">{{ \Carbon\Carbon::parse($reg->route->date)->translatedFormat('l') }}</span>
                        </td>
                        <td class="py-3.5 px-3 font-mono text-slate-600">{{ substr($reg->collected_at ?? '05:00', 0, 5) }}</td>
                        <td class="py-3.5 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700">
                                {{ $reg->route->zone->name ?? 'Zona Huata' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-slate-700 font-medium">{{ $reg->route->collector->name ?? '—' }}</td>
                        <td class="py-3.5 px-3">
                            <span class="text-sm font-black text-[#0f1713]">{{ number_format($reg->liters, 2) }}</span>
                            <span class="text-[10px] text-slate-400">L</span>
                        </td>
                        <td class="py-3.5 px-3 font-bold text-slate-800">
                            S/ {{ number_format($reg->liters * $precioLitro, 2) }}
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            @if($reg->route->reception)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60">
                                    <i class="fa-solid fa-check-double text-[9px] mr-1"></i> Verificado
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    En Recepción
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 font-medium">No se encontraron entregas de leche en el rango seleccionado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100">
            {{ $registrosDiarios->links() }}
        </div>

        @elseif($filtro === 'semana')
        <!-- 2. VISTA AGRUPADA POR SEMANA -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Semana</th>
                        <th class="pb-3 px-3">Rango de Días</th>
                        <th class="pb-3 px-3">Días Entregados</th>
                        <th class="pb-3 px-3">Total Litros</th>
                        <th class="pb-3 px-3">Promedio Diario</th>
                        <th class="pb-3 px-3 text-right">Importe Bruto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($resumenSemanas as $sem)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 font-bold text-slate-900 font-mono">{{ $sem->semana }}</td>
                        <td class="py-3.5 px-3 font-medium text-slate-700">{{ $sem->rango }}</td>
                        <td class="py-3.5 px-3 text-slate-600">{{ $sem->dias_entregados }} días</td>
                        <td class="py-3.5 px-3 font-black text-slate-900 text-sm">{{ number_format($sem->total_litros, 2) }} L</td>
                        <td class="py-3.5 px-3 text-slate-500">{{ number_format($sem->promedio_diario, 2) }} L/día</td>
                        <td class="py-3.5 px-3 text-right font-black text-emerald-800 text-sm">
                            S/ {{ number_format($sem->total_bruto, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 font-medium">No hay entregas semanales registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @elseif($filtro === 'mes')
        <!-- 3. VISTA AGRUPADA POR MES -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Mes</th>
                        <th class="pb-3 px-3">Período</th>
                        <th class="pb-3 px-3">Días de Acopio</th>
                        <th class="pb-3 px-3">Total Litros</th>
                        <th class="pb-3 px-3">Promedio por Día</th>
                        <th class="pb-3 px-3 text-right">Importe Bruto Estimado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($resumenMeses as $m)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 font-bold text-slate-900 text-sm capitalize">{{ $m->mes_nombre }}</td>
                        <td class="py-3.5 px-3 font-mono text-slate-500">{{ $m->mes_key }}</td>
                        <td class="py-3.5 px-3 text-slate-600">{{ $m->dias_entregados }} días</td>
                        <td class="py-3.5 px-3 font-black text-slate-900 text-sm">{{ number_format($m->total_litros, 2) }} L</td>
                        <td class="py-3.5 px-3 text-slate-500">{{ number_format($m->promedio_diario, 2) }} L/día</td>
                        <td class="py-3.5 px-3 text-right font-black text-emerald-800 text-sm">
                            S/ {{ number_format($m->total_bruto, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 font-medium">No hay entregas mensuales registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
