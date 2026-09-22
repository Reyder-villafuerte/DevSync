@extends('layouts.app')

@section('title', 'Historial de Recibos Emitidos')

@section('content')
<div class="space-y-6">
    <!-- PESTAÑAS DE NAVEGACIÓN ENTRE VENTAS Y RECIBOS -->
    <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3 no-print">
        <a href="{{ route('ventas.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition text-slate-500 hover:text-slate-900 hover:bg-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Ventas de Hoy</span>
        </a>
        <a href="{{ route('ventas.receipts') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition bg-spark-lime text-spark-dark shadow-sm flex items-center gap-2">
            <i class="fa-solid fa-receipt"></i>
            <span>Historial de Recibos</span>
        </a>
    </div>

    <!-- Header Banner -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] bg-slate-900 text-spark-lime font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    Archivo General
                </span>
                <span class="text-xs text-slate-400 font-medium">Comprobantes & Facturación</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">
                Historial de Recibos Emitidos
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Consulta y reimpresión de todos los comprobantes emitidos en la planta, tanto en efectivo como a cuenta de leche.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-slate-50 border border-slate-200 px-4 py-2.5 rounded-2xl text-right shadow-sm">
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Total Recibos</span>
                <span class="text-xl font-black text-slate-800 tracking-tight">{{ $totalRecibos }} <span class="text-xs font-bold text-slate-400">Docs</span></span>
            </div>
            <button onclick="window.print()" class="px-4 py-2.5 rounded-2xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition no-print">
                <i class="fa-solid fa-print"></i> <span>Imprimir Listado</span>
            </button>
        </div>
    </div>

    <!-- MÉTRICAS RESUMEN HISTÓRICO -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Cobrado en Efectivo</span>
            <div class="mt-2">
                <span class="text-2xl font-black text-emerald-700 font-mono">S/ {{ number_format($totalRecaudadoEfectivo, 2) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Recaudado físicamente en caja</p>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total a Cuenta de Leche</span>
            <div class="mt-2">
                <span class="text-2xl font-black text-amber-700 font-mono">S/ {{ number_format($totalDeducidoLeche, 2) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Deducciones aplicadas a proveedores</p>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Unidades Despachadas</span>
            <div class="mt-2">
                <span class="text-2xl font-black text-slate-900 font-mono">{{ $totalUnidadesHistorico }} <span class="text-xs font-bold text-slate-400">unidades</span></span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Salidas históricas de almacén</p>
        </div>
    </div>

    <!-- TABLA DE RECIBOS HISTÓRICOS -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <!-- Buscador y Filtros -->
        <form action="{{ route('ventas.receipts') }}" method="GET" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                <div class="relative flex-1 sm:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar cliente, DNI o #REC..."
                        class="text-xs pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl w-full focus:bg-white focus:ring-2 focus:ring-spark-lime">
                </div>

                <input type="date" name="fecha" value="{{ request('fecha') }}" onchange="this.form.submit()" title="Filtrar por fecha"
                    class="text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white font-mono text-slate-700">

                <select name="tipo_cliente" onchange="this.form.submit()" class="text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white text-slate-700 font-medium">
                    <option value="">Todos los clientes</option>
                    <option value="proveedor" {{ request('tipo_cliente') == 'proveedor' ? 'selected' : '' }}>Proveedores</option>
                    <option value="mayorista" {{ request('tipo_cliente') == 'mayorista' ? 'selected' : '' }}>Mayoristas</option>
                    <option value="local" {{ request('tipo_cliente') == 'local' ? 'selected' : '' }}>Locales</option>
                </select>

                <button type="submit" class="bg-spark-dark hover:bg-black text-white px-4 py-2.5 rounded-2xl text-xs font-bold transition">
                    Filtrar
                </button>

                @if(request('search') || request('fecha') || request('tipo_cliente'))
                <a href="{{ route('ventas.receipts') }}" class="text-xs text-slate-400 hover:text-slate-800 font-bold px-2 py-2">
                    Limpiar
                </a>
                @endif
            </div>

            <span class="text-xs text-slate-400 font-medium">Mostrando {{ $sales->count() }} de {{ $sales->total() }} recibos</span>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3.5 rounded-l-xl w-16">ID</th>
                        <th class="p-3.5">Recibo</th>
                        <th class="p-3.5">Cliente</th>
                        <th class="p-3.5">Categoría</th>
                        <th class="p-3.5 text-center">Unidades</th>
                        <th class="p-3.5">Detalle</th>
                        <th class="p-3.5">Total</th>
                        <th class="p-3.5">Flujo / Pago</th>
                        <th class="p-3.5">Cierre Caja</th>
                        <th class="p-3.5">Vendedor</th>
                        <th class="p-3.5">Fecha</th>
                        <th class="p-3.5 rounded-r-xl text-right">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-3.5 font-mono text-slate-400">{{ $sale->id }}</td>
                        <td class="p-3.5 font-mono font-bold text-slate-800">{{ $sale->receipt_number }}</td>
                        <td class="p-3.5 font-bold text-slate-900">
                            {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                            @if($sale->customer->dni_ruc)
                            <span class="block text-[10px] text-slate-400 font-normal font-mono">DNI: {{ $sale->customer->dni_ruc }}</span>
                            @endif
                        </td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                {{ $sale->customer->type === 'proveedor' ? 'bg-blue-100 text-blue-900' : ($sale->customer->type === 'mayorista' ? 'bg-lime-100 text-spark-limeText' : 'bg-slate-100 text-slate-700') }}">
                                {{ $sale->customer->type }}
                            </span>
                        </td>
                        <td class="p-3.5 text-center font-black text-slate-800 text-sm">{{ $sale->cheese_molds_quantity }}</td>
                        <td class="p-3.5 text-slate-600 font-semibold text-[11px]">{{ $sale->resumenItems() }}</td>
                        <td class="p-3.5 font-black text-slate-900 text-sm font-mono">S/ {{ number_format($sale->total_amount, 2) }}</td>
                        <td class="p-3.5">
                            @if($sale->payment_method === 'descuento_leche')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                <i class="fa-solid fa-receipt text-[9px]"></i> A Cuenta Leche
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <i class="fa-solid fa-money-bill-wave text-[9px]"></i> Efectivo
                            </span>
                            @endif
                        </td>
                        <td class="p-3.5">
                            @if($sale->closure_id)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600" title="Arqueado en cierre #{{ $sale->closure_id }}">
                                Cerrado
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Pendiente de arqueo">
                                Activo
                            </span>
                            @endif
                        </td>
                        <td class="p-3.5 text-slate-500">{{ $sale->seller ? $sale->seller->name : 'Planta' }}</td>
                        <td class="p-3.5 text-slate-400 font-mono text-[11px]">{{ $sale->sold_at }}</td>
                        <td class="p-3.5 text-right">
                            <a href="{{ route('ventas.receipt', $sale->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-spark-dark text-spark-lime hover:bg-black font-bold text-xs shadow-sm transition">
                                <i class="fa-solid fa-receipt text-xs"></i> <span>Ver Recibo</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center text-slate-400 font-medium">No se encontraron recibos emitidos con los criterios especificados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <x-paginador :coleccion="$sales" />
        </div>
    </div>
</div>
@endsection
