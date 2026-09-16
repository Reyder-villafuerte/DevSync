@extends('layouts.app')

@section('title', 'Flujo de Caja — Ingresos y Egresos')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Flujo de Caja y Control Financiero</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Admin Finanzas</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Consolidación en tiempo real de ingresos por venta de quesos, egresos a proveedores de leche y pagos de personal.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Selector de Período -->
            <div class="flex items-center bg-slate-100 p-1 rounded-2xl text-xs font-bold">
                <a href="{{ route('admin.finanzas.index', ['period' => 'hoy']) }}" 
                   class="px-3 py-1.5 rounded-xl transition {{ $period === 'hoy' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Hoy
                </a>
                <a href="{{ route('admin.finanzas.index', ['period' => 'semana']) }}" 
                   class="px-3 py-1.5 rounded-xl transition {{ $period === 'semana' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Semana
                </a>
                <a href="{{ route('admin.finanzas.index', ['period' => 'mes']) }}" 
                   class="px-3 py-1.5 rounded-xl transition {{ $period === 'mes' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Mes
                </a>
                <a href="{{ route('admin.finanzas.index', ['period' => 'todo']) }}" 
                   class="px-3 py-1.5 rounded-xl transition {{ $period === 'todo' ? 'bg-[#0f1713] text-[#bef264] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Todo
                </a>
            </div>

            <button type="button" onclick="openExpenseModal()" class="px-4 py-2.5 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] text-xs font-bold rounded-2xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Registrar Egreso / Pago</span>
            </button>
        </div>
    </div>

    <!-- 4 Cards de Métricas Principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Ingresos (Ventas de Queso) -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-down-left text-emerald-600"></i> Ingresos (Ventas)
                    </span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-black">
                        <i class="fa-solid fa-cart-arrow-down"></i>
                    </span>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                    S/ {{ number_format($totalIncome, 2) }}
                </div>
                <div class="mt-2 text-[11px] text-slate-500 space-y-0.5">
                    <div class="flex justify-between">
                        <span>Efectivo en caja:</span>
                        <strong class="text-emerald-700">S/ {{ number_format($incomeCash, 2) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>A cuenta de leche:</span>
                        <strong class="text-slate-700">S/ {{ number_format($incomeMilkCredit, 2) }}</strong>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-medium">
                <span>{{ $totalMoldsSold }} moldes de queso</span>
                <a href="{{ route('ventas.receipts') }}" class="text-[#0f1713] hover:underline font-bold">Ver recibos &rarr;</a>
            </div>
        </div>

        <!-- 2. Egresos Proveedores (Leche) -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-up-right text-amber-600"></i> Proveedores Leche
                    </span>
                    <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs font-black">
                        <i class="fa-solid fa-cow"></i>
                    </span>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                    S/ {{ number_format($expenseSuppliers, 2) }}
                </div>
                <div class="mt-2 text-[11px] text-slate-500 space-y-0.5">
                    <div class="flex justify-between">
                        <span>Litros liquidados:</span>
                        <strong class="text-slate-700">{{ number_format($totalLitersPaid, 1) }} L</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Liquidaciones autorizadas:</span>
                        <strong class="text-amber-800">{{ $settlements->count() }} pagos</strong>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-medium">
                <span>Rutas 4:30 AM</span>
                <a href="{{ route('admin.pagos.autorizacion') }}" class="text-[#0f1713] hover:underline font-bold">Autorizaciones &rarr;</a>
            </div>
        </div>

        <!-- 3. Egresos Personal y Operativos -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-up-right text-rose-600"></i> Personal y Gastos
                    </span>
                    <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-black">
                        <i class="fa-solid fa-users"></i>
                    </span>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                    S/ {{ number_format($expenseStaff + $expenseOperations, 2) }}
                </div>
                <div class="mt-2 text-[11px] text-slate-500 space-y-0.5">
                    <div class="flex justify-between">
                        <span>Pago de personal:</span>
                        <strong class="text-rose-700">S/ {{ number_format($expenseStaff, 2) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Ruta / Combustible / Insumos:</span>
                        <strong class="text-slate-700">S/ {{ number_format($expenseOperations, 2) }}</strong>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-medium">
                <span>{{ $allExpenses->count() }} egresos registrados</span>
                <button type="button" onclick="openExpenseModal()" class="text-[#0f1713] hover:underline font-bold">+ Nuevo</button>
            </div>
        </div>

        <!-- 4. Balance Neto Operativo -->
        <div class="bg-[#0f1713] text-white rounded-3xl p-5 shadow-[0_4px_20px_rgba(15,23,19,0.15)] flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#bef264]/10 rounded-full blur-xl pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-[#bef264] uppercase tracking-wider">Balance Operativo Neto</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $netBalance >= 0 ? 'bg-[#bef264] text-[#0f1713]' : 'bg-rose-500 text-white' }}">
                        {{ $netBalance >= 0 ? 'Superávit' : 'Déficit' }}
                    </span>
                </div>
                <div class="text-3xl font-black {{ $netBalance >= 0 ? 'text-[#bef264]' : 'text-rose-400' }} tracking-tight">
                    {{ $netBalance >= 0 ? '+ ' : '- ' }}S/ {{ number_format(abs($netBalance), 2) }}
                </div>
                <div class="mt-2 text-[11px] text-slate-300 space-y-0.5">
                    <div class="flex justify-between">
                        <span>Ingresos totales:</span>
                        <span class="font-bold text-emerald-400">+ S/ {{ number_format($totalIncome, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Egresos totales:</span>
                        <span class="font-bold text-rose-400">- S/ {{ number_format($totalExpenses, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-white/10 text-[11px] text-slate-400">
                <span>Estado de caja consolidado</span>
            </div>
        </div>
    </div>

    <!-- Tabla de Flujo Unificado: Movimientos Detallados -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900">Detalle de Movimientos Financieros</h2>
                    <p class="text-xs text-slate-400">Registro cronológico de ventas, liquidaciones a productores y egresos operativos/personal.</p>
                </div>
            </div>

            <!-- Filtro rápido por tipo de movimiento -->
            <div class="flex items-center gap-1.5">
                <button type="button" onclick="filterTable('todos')" id="tab-todos" class="filter-tab px-3 py-1.5 rounded-xl text-xs font-bold bg-[#0f1713] text-[#bef264] transition">
                    Todos
                </button>
                <button type="button" onclick="filterTable('ingreso')" id="tab-ingreso" class="filter-tab px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                    Ingresos ({{ $allSales->count() }})
                </button>
                <button type="button" onclick="filterTable('proveedor')" id="tab-proveedor" class="filter-tab px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                    Proveedores ({{ $settlements->count() }})
                </button>
                <button type="button" onclick="filterTable('personal')" id="tab-personal" class="filter-tab px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                    Personal & Ops ({{ $allExpenses->count() }})
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Fecha</th>
                        <th class="pb-3 px-3">Tipo / Flujo</th>
                        <th class="pb-3 px-3">Concepto / Descripción</th>
                        <th class="pb-3 px-3">Beneficiario / Responsable</th>
                        <th class="pb-3 px-3">Método / Canal</th>
                        <th class="pb-3 px-3 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <!-- 1. Ventas (Ingresos) -->
                    @foreach($allSales as $sale)
                    <tr class="mov-row mov-ingreso hover:bg-slate-50/60 transition">
                        <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                            {{ $sale->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1 w-fit">
                                <i class="fa-solid fa-circle-arrow-down text-[9px]"></i> Ingreso Venta
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-800">
                                Venta de {{ $sale->cheese_molds_quantity }} molde(s) de queso
                            </div>
                            <div class="text-[10px] text-slate-400">Recibo: {{ $sale->receipt_number ?: '#REC-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</div>
                        </td>
                        <td class="py-3 px-3 text-slate-600">
                            {{ $sale->customer ? $sale->customer->name : 'Cliente Mostrador' }}
                            @if($sale->seller)
                                <span class="text-[10px] text-slate-400 block">Cajero: {{ $sale->seller->name }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            @if($sale->payment_method === 'descuento_leche')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                    A Cuenta Leche
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-200">
                                    Efectivo Caja
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-right font-black text-emerald-700 text-sm whitespace-nowrap">
                            + S/ {{ number_format($sale->total_amount, 2) }}
                        </td>
                    </tr>
                    @endforeach

                    <!-- 2. Proveedores (Egresos Liquidación Leche) -->
                    @foreach($settlements as $st)
                    <tr class="mov-row mov-proveedor hover:bg-slate-50/60 transition">
                        <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                            {{ $st->paid_at ? $st->paid_at->format('Y-m-d H:i') : $st->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200 flex items-center gap-1 w-fit">
                                <i class="fa-solid fa-cow text-[9px]"></i> Pago Proveedor
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-800">
                                Liquidación semanal: {{ $st->total_liters }} L de leche
                            </div>
                            <div class="text-[10px] text-slate-400">
                                {{ $st->settlement_code ?: '#SOBRE-' . str_pad($st->id, 5, '0', STR_PAD_LEFT) }} · Desc: S/ {{ number_format($st->deductions_total, 2) }}
                            </div>
                        </td>
                        <td class="py-3 px-3 text-slate-600">
                            <strong>{{ $st->producer ? $st->producer->name : 'Productor Huata' }}</strong>
                            <span class="text-[10px] text-slate-400 block">DNI: {{ $st->producer ? $st->producer->dni : '—' }}</span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                Sobre Ruta Viernes
                            </span>
                        </td>
                        <td class="py-3 px-3 text-right font-black text-amber-700 text-sm whitespace-nowrap">
                            - S/ {{ number_format($st->net_total, 2) }}
                        </td>
                    </tr>
                    @endforeach

                    <!-- 3. Personal y Gastos Operativos (Egresos) -->
                    @foreach($allExpenses as $exp)
                    <tr class="mov-row mov-personal hover:bg-slate-50/60 transition">
                        <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                            {{ $exp->expense_date->format('Y-m-d') }}
                        </td>
                        <td class="py-3 px-3">
                            @if($exp->category === 'pago_personal')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-200 flex items-center gap-1 w-fit">
                                    <i class="fa-solid fa-user-check text-[9px]"></i> Pago Personal
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-200 flex items-center gap-1 w-fit">
                                    <i class="fa-solid fa-gas-pump text-[9px]"></i> Gasto Operativo
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-800">{{ $exp->description }}</div>
                            @if($exp->receipt_number)
                                <div class="text-[10px] text-slate-400">Comprobante: {{ $exp->receipt_number }}</div>
                            @endif
                            @if($exp->notes)
                                <div class="text-[10px] text-slate-500 italic">{{ $exp->notes }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-slate-600">
                            <strong>{{ $exp->beneficiary_name ?: ($exp->staff ? $exp->staff->name : 'Personal') }}</strong>
                            @if($exp->staff)
                                <span class="text-[10px] text-slate-400 block">Rol: {{ ucfirst($exp->staff->role) }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                                {{ $exp->payment_method }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-right font-black text-rose-700 text-sm whitespace-nowrap">
                            - S/ {{ number_format($exp->amount, 2) }}
                        </td>
                    </tr>
                    @endforeach

                    @if($allSales->isEmpty() && $settlements->isEmpty() && $allExpenses->isEmpty())
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                            <i class="fa-solid fa-folder-open text-2xl text-slate-300 block mb-2"></i>
                            No hay movimientos registrados para el período seleccionado.
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Registrar Egreso / Pago de Personal -->
<div id="expenseModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center text-xs font-bold">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Registrar Egreso / Pago de Personal</h3>
            </div>
            <button type="button" onclick="closeExpenseModal()" class="text-slate-400 hover:text-slate-600 text-base">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.finanzas.expense.store') }}" method="POST" class="space-y-3.5">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Categoría</label>
                    <select name="category" id="categorySelect" required onchange="handleCategoryChange()" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 font-bold text-slate-800 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                        <option value="pago_personal">Pago de Personal / Planilla</option>
                        <option value="combustible_ruta">Combustible Camioneta (Acopio 4:30 AM)</option>
                        <option value="insumos_planta">Insumos de Quesería / Planta</option>
                        <option value="mantenimiento">Mantenimiento de Equipos / Lactoscan</option>
                        <option value="otros">Otros Gastos Operativos</option>
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Fecha</label>
                    <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                </div>
            </div>

            <!-- Selector de Personal si la categoría es pago_personal -->
            <div id="staffSelectGroup">
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Seleccionar Personal</label>
                <select name="user_id" id="staffSelect" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    <option value="">-- Seleccionar personal de la empresa --</option>
                    @foreach($staffMembers as $sm)
                        <option value="{{ $sm->id }}">{{ $sm->name }} ({{ ucfirst(str_replace('_', ' ', $sm->role)) }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Beneficiario / Destinatario</label>
                    <input type="text" name="beneficiary_name" id="beneficiaryInput" placeholder="Nombre completo..." class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Monto (S/)</label>
                    <input type="number" step="0.01" name="amount" required placeholder="0.00" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 font-bold text-slate-900 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Concepto / Detalle del Egreso</label>
                <input type="text" name="description" required placeholder="Ej. Pago quincenal acopiador zona 1, compra de cuajo..." class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Forma de Pago</label>
                    <select name="payment_method" required class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                        <option value="efectivo">Efectivo en mano</option>
                        <option value="transferencia">Transferencia bancaria</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">N° Comprobante / Recibo (opc)</label>
                    <input type="text" name="receipt_number" placeholder="Ej. REC-0142" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                </div>
            </div>

            <div class="flex gap-2 justify-end pt-3 border-t border-slate-100">
                <button type="button" onclick="closeExpenseModal()" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] text-xs font-bold rounded-2xl transition flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-check"></i> Guardar Egreso
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function filterTable(type) {
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('bg-[#0f1713]', 'text-[#bef264]');
        tab.classList.add('text-slate-600');
    });

    const activeTab = document.getElementById('tab-' + type);
    if (activeTab) {
        activeTab.classList.remove('text-slate-600');
        activeTab.classList.add('bg-[#0f1713]', 'text-[#bef264]');
    }

    const rows = document.querySelectorAll('.mov-row');
    rows.forEach(row => {
        if (type === 'todos') {
            row.style.display = '';
        } else if (type === 'ingreso' && row.classList.contains('mov-ingreso')) {
            row.style.display = '';
        } else if (type === 'proveedor' && row.classList.contains('mov-proveedor')) {
            row.style.display = '';
        } else if (type === 'personal' && row.classList.contains('mov-personal')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openExpenseModal() {
    document.getElementById('expenseModal').classList.remove('hidden');
}

function closeExpenseModal() {
    document.getElementById('expenseModal').classList.add('hidden');
}

function handleCategoryChange() {
    const category = document.getElementById('categorySelect').value;
    const staffGroup = document.getElementById('staffSelectGroup');
    if (category === 'pago_personal') {
        staffGroup.classList.remove('hidden');
    } else {
        staffGroup.classList.add('hidden');
    }
}
</script>
@endsection
