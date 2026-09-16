@extends('layouts.app')

@section('title', 'Ventas de Hoy & Caja')

@section('content')
<div class="space-y-6">
    <!-- PESTAÑAS DE NAVEGACIÓN: VENTAS Y RECIBOS -->
    <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3 no-print">
        <a href="{{ route('ventas.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition bg-spark-lime text-spark-dark shadow-sm flex items-center gap-2">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Ventas de Hoy</span>
        </a>
        <a href="{{ route('ventas.receipts') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition text-slate-500 hover:text-slate-900 hover:bg-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-receipt"></i>
            <span>Historial de Recibos</span>
        </a>
    </div>

    <!-- Header Banner -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] bg-spark-lime/30 text-spark-dark font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider border border-spark-lime">
                    Turno Activo
                </span>
                <span class="text-xs text-slate-400 font-medium">Planta Quesera Huata • {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">
                Ventas de Hoy & Caja
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Registro de salidas de almacén del turno actual. Al cerrar caja, el balance diario se liquida y la lista vuelve a blanco.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-slate-50 border border-slate-200 px-4 py-2.5 rounded-2xl text-right shadow-sm">
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Stock Almacén</span>
                <span class="text-xl font-black text-slate-800 tracking-tight">{{ (int)$stockQueso }} <span class="text-xs font-bold text-slate-400">Moldes</span></span>
            </div>

            <a href="{{ route('ventas.create') }}" class="bg-spark-dark hover:bg-black text-spark-lime font-black px-5 py-3 rounded-2xl shadow-sm text-xs transition flex items-center gap-2">
                <i class="fa-solid fa-cart-plus text-sm"></i>
                <span>+ Nueva Venta en Caja</span>
            </a>

            <!-- Botón Cierre de Caja -->
            <button type="button" id="btnToggleCierre" onclick="toggleCierreCaja()" 
                class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-sm border border-slate-300 bg-white hover:bg-slate-100 text-slate-800">
                <i class="fa-solid fa-calculator text-sm text-emerald-700"></i>
                <span id="txtBtnCierre">Cierre de Caja</span>
            </button>
        </div>
    </div>

    <!-- PANEL DE CIERRE DE CAJA / ARQUEO DIARIO (DESPLEGABLE) -->
    <div id="cierreCajaPanel" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-6 {{ request('cierre') == '1' || request('fecha') ? '' : 'hidden' }}">
        <!-- Selector de fecha y título de Cierre -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-spark-dark text-spark-lime flex items-center justify-center text-base shadow-sm">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900">Arqueo y Cierre de Caja</h2>
                    <p class="text-xs text-slate-400">Conciliación de dinero en efectivo vs descuentos en leche de la fecha</p>
                </div>
            </div>

            <!-- Filtro de fecha para el Cierre -->
            <form method="GET" action="{{ route('ventas.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="cierre" value="1">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                <label for="fecha" class="text-xs font-bold text-slate-500">Fecha:</label>
                <input type="date" id="fecha" name="fecha" value="{{ $fechaCierre }}" onchange="this.form.submit()"
                    class="text-xs px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-spark-lime font-mono font-bold text-slate-800">
                <button type="button" onclick="window.print()" class="px-3.5 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition no-print" title="Imprimir hoja de arqueo">
                    <i class="fa-solid fa-print text-xs"></i> <span>Imprimir Cierre</span>
                </button>
                <button type="button" onclick="toggleCierreCaja()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition no-print ml-1" title="Cerrar cuadro de cierre">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </form>
        </div>

        <!-- TARJETAS DE MÉTODOS DE PAGO Y DINERO REAL -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- 1. Dinero Efectivo Real en Caja -->
            <div class="p-6 rounded-3xl bg-emerald-50 border-2 border-emerald-300/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full">
                        Efectivo Real en Caja
                    </span>
                    <i class="fa-solid fa-money-bill-wave text-2xl text-emerald-500/70"></i>
                </div>
                <div class="mt-3">
                    <span class="text-3xl font-black text-emerald-950 tracking-tight">S/ {{ number_format($efectivoTotal, 2) }}</span>
                </div>
                <p class="text-xs text-emerald-800 font-medium mt-2 leading-tight">
                    <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> Dinero físico que debe entregar el cajero al final del turno.
                </p>
            </div>

            <!-- 2. Dinero a Cuenta de Leche (Proveedores - Sin Efectivo) -->
            <div class="p-6 rounded-3xl bg-amber-50 border-2 border-amber-300/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-900 bg-amber-100 px-2.5 py-0.5 rounded-full">
                        A Cuenta de Leche (Crédito)
                    </span>
                    <i class="fa-solid fa-hand-holding-dollar text-2xl text-amber-500/70"></i>
                </div>
                <div class="mt-3">
                    <span class="text-3xl font-black text-amber-950 tracking-tight">S/ {{ number_format($descuentoLecheTotal, 2) }}</span>
                </div>
                <p class="text-xs text-amber-800 font-medium mt-2 leading-tight">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1"></i> <strong>No ingresó efectivo:</strong> Se descuenta del pago semanal del proveedor.
                </p>
            </div>

            <!-- 3. Total Facturado y Despacho Global -->
            <div class="p-6 rounded-3xl bg-spark-dark text-white shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-wider text-spark-lime bg-white/10 px-2.5 py-0.5 rounded-full">
                            Total Despachado
                        </span>
                        <i class="fa-solid fa-cheese text-2xl text-spark-lime/70"></i>
                    </div>
                    <div class="mt-3">
                        <span class="text-3xl font-black text-white tracking-tight">S/ {{ number_format($totalMontoDia, 2) }}</span>
                    </div>
                </div>
                <p class="text-xs text-slate-300 mt-2">
                    <strong>{{ $totalMoldesDia }}</strong> moldes entregados en <strong>{{ $totalTransaccionesDia }}</strong> transacciones registradas.
                </p>
            </div>
        </div>

        <!-- CUADRO DE RESUMEN POR CATEGORÍA DE CLIENTE -->
        <div class="pt-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Resumen Consolidado por Categoría de Cliente ({{ \Carbon\Carbon::parse($fechaCierre)->format('d/m/Y') }})
                </h3>
            </div>

            <div class="overflow-x-auto border border-slate-100 rounded-2xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4">Tipo de Cliente</th>
                            <th class="py-3 px-4">Tarifa / Condición</th>
                            <th class="py-3 px-4 text-center">Moldes de Queso</th>
                            <th class="py-3 px-4 text-right">Efectivo Cobrado</th>
                            <th class="py-3 px-4 text-right">A Cuenta Leche</th>
                            <th class="py-3 px-4 text-right">Subtotal Facturado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Proveedor -->
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $resumenClientes['proveedor']['nombre'] }}</span>
                                <span class="text-[10px] text-slate-400">Productores inscritos con entrega de leche</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                    S/ 18.00 / molde
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-black text-slate-900 text-sm">
                                {{ $resumenClientes['proveedor']['moldes'] }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                                S/ {{ number_format($resumenClientes['proveedor']['efectivo'], 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-amber-700">
                                S/ {{ number_format($resumenClientes['proveedor']['descuento_leche'], 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-slate-900">
                                S/ {{ number_format($resumenClientes['proveedor']['total_monto'], 2) }}
                            </td>
                        </tr>

                        <!-- Mayorista -->
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $resumenClientes['mayorista']['nombre'] }}</span>
                                <span class="text-[10px] text-slate-400">Comerciantes mayoristas o compras ≥ 10 moldes</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-lime-50 text-spark-limeText border border-lime-200">
                                    S/ 19.00 / molde
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-black text-slate-900 text-sm">
                                {{ $resumenClientes['mayorista']['moldes'] }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                                S/ {{ number_format($resumenClientes['mayorista']['efectivo'], 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-400">
                                S/ 0.00
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-slate-900">
                                S/ {{ number_format($resumenClientes['mayorista']['total_monto'], 2) }}
                            </td>
                        </tr>

                        <!-- Local -->
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $resumenClientes['local']['nombre'] }}</span>
                                <span class="text-[10px] text-slate-400">Público en general y ventas minoristas</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                    S/ 20.00 / molde
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-black text-slate-900 text-sm">
                                {{ $resumenClientes['local']['moldes'] }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                                S/ {{ number_format($resumenClientes['local']['efectivo'], 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-400">
                                S/ 0.00
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-slate-900">
                                S/ {{ number_format($resumenClientes['local']['total_monto'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-200">
                        <tr>
                            <td colspan="2" class="py-3.5 px-4 text-slate-900 font-black text-xs uppercase tracking-wider">
                                TOTALES CIERRE DE CAJA
                            </td>
                            <td class="py-3.5 px-4 text-center font-black text-slate-900 text-base">
                                {{ $totalMoldesDia }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-emerald-800 text-sm">
                                S/ {{ number_format($efectivoTotal, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-amber-800 text-sm">
                                S/ {{ number_format($descuentoLecheTotal, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-slate-900 text-sm">
                                S/ {{ number_format($totalMontoDia, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ACCIÓN PARA CERRAR CAJA DE HOY (DEJA LA BANDEJA EN BLANCO) -->
        @if($ventasHoy->isNotEmpty() && $fechaCierre === $today)
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-emerald-50/50 p-4 rounded-2xl border border-emerald-200/60 no-print">
            <div class="text-xs text-emerald-900">
                <i class="fa-solid fa-circle-info text-emerald-600 mr-1.5 text-sm"></i>
                Al confirmar el cierre, se conciliarán <strong>S/ {{ number_format($efectivoTotal, 2) }}</strong> en efectivo y la tabla de ventas de hoy volverá a blanco.
            </div>
            <form method="POST" action="{{ route('ventas.close-cash') }}" onsubmit="return confirm('¿Confirmas el cierre de caja de hoy?\n\n• Efectivo en caja: S/ {{ number_format($efectivoTotal, 2) }}\n• Descuento en leche: S/ {{ number_format($descuentoLecheTotal, 2) }}\n• Moldes: {{ $totalMoldesDia }}\n\nLa bandeja de ventas de hoy volverá a blanco.');">
                @csrf
                <button type="submit" class="px-6 py-2.5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs shadow-md transition flex items-center gap-2">
                    <i class="fa-solid fa-lock"></i>
                    <span>Confirmar y Cerrar Caja</span>
                </button>
            </form>
        </div>
        @elseif($closureToday && $fechaCierre === $today)
        <div class="pt-4 border-t border-slate-100 flex items-center gap-2 text-xs text-emerald-800 font-bold bg-emerald-50 p-3.5 rounded-2xl border border-emerald-200">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>Caja de hoy cerrada a las {{ $closureToday->closed_at ? $closureToday->closed_at->format('H:i') : '' }} por {{ $closureToday->closer ? $closureToday->closer->name : 'Cajero' }}. Efectivo arqueado: S/ {{ number_format($closureToday->total_cash, 2) }}.</span>
        </div>
        @endif
    </div>

    <!-- TABLA DE VENTAS DE HOY (SE PONE EN BLANCO AL CERRAR CAJA) -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-slate-900">Ventas de hoy</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase {{ $ventasHoy->isNotEmpty() ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-100 text-slate-600' }}">
                        {{ $ventasHoy->isNotEmpty() ? 'Turno Activo (' . $ventasHoy->count() . ')' : 'Bandeja en Blanco' }}
                    </span>
                </div>
                <p class="text-xs text-slate-400">Ventas activas de la jornada actual ({{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}) pendientes de cierre de caja</p>
            </div>

            <form action="{{ route('ventas.index') }}" method="GET" class="w-full sm:w-auto flex gap-2 no-print">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar cliente o recibo..."
                    class="text-xs px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-full w-full sm:w-64 focus:bg-white focus:ring-2 focus:ring-spark-lime">
                <button type="submit" class="bg-spark-dark hover:bg-black text-white px-4 py-2 rounded-full text-xs font-bold transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3.5 rounded-l-xl">Recibo</th>
                        <th class="p-3.5">Cliente</th>
                        <th class="p-3.5">Categoría</th>
                        <th class="p-3.5 text-center">Moldes</th>
                        <th class="p-3.5">P. Unitario</th>
                        <th class="p-3.5">Total</th>
                        <th class="p-3.5">Flujo / Pago</th>
                        <th class="p-3.5">Vendedor</th>
                        <th class="p-3.5">Hora</th>
                        <th class="p-3.5 rounded-r-xl text-right">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($ventasHoy as $sale)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-3.5 font-mono font-bold text-slate-800">{{ $sale->receipt_number }}</td>
                        <td class="p-3.5 font-bold text-slate-900">
                            {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                        </td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                {{ $sale->customer->type === 'proveedor' ? 'bg-blue-100 text-blue-900' : ($sale->customer->type === 'mayorista' ? 'bg-lime-100 text-spark-limeText' : 'bg-slate-100 text-slate-700') }}">
                                {{ $sale->customer->type }}
                            </span>
                        </td>
                        <td class="p-3.5 text-center font-black text-slate-800 text-sm">{{ $sale->cheese_molds_quantity }}</td>
                        <td class="p-3.5 text-slate-600 font-semibold font-mono">S/ {{ number_format($sale->unit_price, 2) }}</td>
                        <td class="p-3.5 font-black text-slate-900 text-sm font-mono">S/ {{ number_format($sale->total_amount, 2) }}</td>
                        <td class="p-3.5">
                            @if($sale->payment_method === 'descuento_leche')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Descontado de la liquidación semanal del proveedor">
                                    <i class="fa-solid fa-receipt text-[9px]"></i> A Cuenta Leche
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200" title="Efectivo cobrado en ventanilla">
                                    <i class="fa-solid fa-money-bill-wave text-[9px]"></i> Efectivo en Caja
                                </span>
                            @endif
                        </td>
                        <td class="p-3.5 text-slate-500">{{ $sale->seller ? $sale->seller->name : 'Planta' }}</td>
                        <td class="p-3.5 text-slate-400 font-mono text-[11px]">{{ \Carbon\Carbon::parse($sale->sold_at)->format('H:i') }}</td>
                        <td class="p-3.5 text-right">
                            <a href="{{ route('ventas.receipt', $sale->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-spark-dark text-spark-lime hover:bg-black font-bold text-xs shadow-sm transition">
                                <i class="fa-solid fa-receipt text-xs"></i> <span>Recibo</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-12 text-center">
                            <div class="max-w-md mx-auto space-y-3">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl mx-auto">
                                    <i class="fa-solid fa-cash-register"></i>
                                </div>
                                @if($closureToday)
                                    <h4 class="text-sm font-bold text-slate-900">Bandeja en blanco: Caja de hoy cerrada</h4>
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        El arqueo de hoy se completó con un total de <strong>S/ {{ number_format($closureToday->total_cash, 2) }}</strong> en efectivo y <strong>{{ $closureToday->cheese_molds_quantity }}</strong> moldes. Todas las ventas han sido liquidadas y transferidas al historial de recibos.
                                    </p>
                                @else
                                    <h4 class="text-sm font-bold text-slate-900">No hay ventas pendientes en el turno de hoy</h4>
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        Las ventas que registres hoy aparecerán aquí en tiempo real hasta que ejecutes el Cierre de Caja.
                                    </p>
                                @endif
                                <div class="pt-2 flex justify-center gap-3 no-print">
                                    <a href="{{ route('ventas.create') }}" class="px-4 py-2 rounded-xl bg-spark-dark text-spark-lime font-bold text-xs hover:bg-black transition flex items-center gap-1.5 shadow-sm">
                                        <i class="fa-solid fa-cart-plus"></i> <span>+ Registrar Venta</span>
                                    </a>
                                    <a href="{{ route('ventas.receipts') }}" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-receipt"></i> <span>Ver Historial de Recibos</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleCierreCaja() {
        const panel = document.getElementById('cierreCajaPanel');
        const btn = document.getElementById('btnToggleCierre');
        const txt = document.getElementById('txtBtnCierre');
        if (!panel) return;

        const isHidden = panel.classList.contains('hidden');
        if (isHidden) {
            panel.classList.remove('hidden');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (txt) txt.innerText = 'Ocultar Cierre';
            if (btn) {
                btn.classList.add('bg-spark-dark', 'text-white', 'border-spark-dark');
                btn.classList.remove('bg-white', 'text-slate-800');
            }
        } else {
            panel.classList.add('hidden');
            if (txt) txt.innerText = 'Cierre de Caja';
            if (btn) {
                btn.classList.remove('bg-spark-dark', 'text-white', 'border-spark-dark');
                btn.classList.add('bg-white', 'text-slate-800');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const panel = document.getElementById('cierreCajaPanel');
        const btn = document.getElementById('btnToggleCierre');
        const txt = document.getElementById('txtBtnCierre');
        if (panel && !panel.classList.contains('hidden')) {
            if (txt) txt.innerText = 'Ocultar Cierre';
            if (btn) {
                btn.classList.add('bg-spark-dark', 'text-white', 'border-spark-dark');
                btn.classList.remove('bg-white', 'text-slate-800');
            }
        }
    });
</script>
@endpush
@endsection
