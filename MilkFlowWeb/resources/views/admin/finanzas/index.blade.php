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
    <x-tabla
        titulo="Detalle de movimientos financieros"
        descripcion="Ventas, liquidaciones a productores y egresos de planilla y operación, en un solo libro por fecha."
        :coleccion="$movimientos"
        :columnas="6"
        vacio="No hay movimientos registrados para el período seleccionado.">

        <x-slot:acciones>
            <button type="button" onclick="openExpenseModal()"
                class="px-4 py-2.5 rounded-xl bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Agregar egreso
            </button>
        </x-slot:acciones>

        <x-slot:filtros>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <form method="GET" action="{{ route('admin.finanzas.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Concepto o beneficiario..."
                            class="w-60 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-[#bef264]">
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-[#bef264] font-bold text-[10px] uppercase">Buscar</button>
                    @if(request('buscar'))
                    <a href="{{ route('admin.finanzas.index', ['tipo' => $tipo, 'period' => $period]) }}" class="text-[11px] font-bold text-slate-500 underline">Quitar búsqueda</a>
                    @endif
                </form>

                <div class="flex flex-wrap items-center gap-1.5">
                    @php
                        $tiposMovimiento = [
                            'todos' => ['Todos', $allSales->count() + $settlements->count() + $allExpenses->count()],
                            'ingreso' => ['Ingresos', $allSales->count()],
                            'proveedor' => ['Proveedores', $settlements->count()],
                            'personal' => ['Personal y ops', $allExpenses->count()],
                        ];
                    @endphp
                    @foreach($tiposMovimiento as $clave => [$etiqueta, $cuantos])
                    <a href="{{ route('admin.finanzas.index', array_merge(request()->except(['tipo', 'page']), ['tipo' => $clave])) }}"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $tipo === $clave ? 'bg-[#0f1713] text-[#bef264]' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                        <span>{{ $etiqueta }}</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tipo === $clave ? 'bg-white/20' : 'bg-slate-200 text-slate-700' }}">{{ $cuantos }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Fecha</th>
            <th class="text-left py-3 px-4 font-bold">Tipo / flujo</th>
            <th class="text-left py-3 px-4 font-bold">Concepto</th>
            <th class="text-left py-3 px-4 font-bold">Beneficiario</th>
            <th class="text-left py-3 px-4 font-bold">Método / canal</th>
            <th class="text-right py-3 px-4 font-bold">Monto</th>
        </x-slot:encabezados>

        @foreach($movimientos as $mov)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $mov['id'] }}</td>
            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $mov['fecha_texto'] }}</td>
            <td class="py-3 px-4">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border flex items-center gap-1 w-fit {{ $mov['clase_etiqueta'] }}">
                    <i class="fa-solid {{ $mov['icono'] }} text-[9px]"></i> {{ $mov['etiqueta'] }}
                </span>
            </td>
            <td class="py-3 px-4">
                <div class="font-bold text-slate-800">{{ $mov['concepto'] }}</div>
                @if($mov['detalle'])
                <div class="text-[10px] text-slate-400">{{ $mov['detalle'] }}</div>
                @endif
            </td>
            <td class="py-3 px-4 text-slate-600">
                <strong>{{ $mov['beneficiario'] }}</strong>
                @if($mov['beneficiario_detalle'])
                <span class="text-[10px] text-slate-400 block">{{ $mov['beneficiario_detalle'] }}</span>
                @endif
            </td>
            <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $mov['clase_metodo'] }}">{{ $mov['metodo'] }}</span>
            </td>
            <td class="py-3 px-4 text-right font-black text-sm whitespace-nowrap {{ $mov['clase_monto'] }}">
                {{ $mov['signo'] }} S/ {{ number_format($mov['monto'], 2) }}
            </td>
        </tr>
        @endforeach
    </x-tabla>

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
                        <option value="insumos_planta">Insumos de planta</option>
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
