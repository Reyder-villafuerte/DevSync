@extends('layouts.app')

@section('title', 'Nueva Venta en Caja')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex justify-between items-center">
        <div>
            <span class="text-[10px] bg-emerald-100 text-emerald-900 font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Punto de Cobro</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Caja de Despacho de Queso</h1>
            <p class="text-xs text-slate-500">Búsqueda rápida por apellido o DNI y tarifa automática.</p>
        </div>
        <div class="bg-slate-50 border border-slate-200 px-5 py-3 rounded-2xl text-right">
            <span class="text-[10px] text-slate-400 block uppercase font-bold">Stock Disponible</span>
            <span class="text-2xl font-black text-slate-900 tracking-tight">{{ (int)$stockQueso }} <span class="text-xs text-slate-400 font-bold">Moldes</span></span>
        </div>
    </div>

    <form action="{{ route('ventas.store') }}" method="POST" id="salesForm" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-6">
        @csrf

        <!-- SELECCIÓN O ALTA DE CLIENTE -->
        <div class="border-b border-slate-100 pb-6">
            <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-user-tag text-spark-limeText"></i> Identificación del Cliente
            </h3>

            <!-- Buscador pill con autocomplete -->
            <div class="mb-4">
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Buscar Cliente por Apellido o DNI:</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="customerSearch" placeholder="Empieza a escribir el apellido del cliente..." 
                        class="w-full text-xs pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-spark-lime focus:border-transparent transition">
                    <div id="searchResults" class="absolute z-10 left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl hidden max-h-48 overflow-y-auto"></div>
                </div>
            </div>

            <!-- Cliente Seleccionado Badge -->
            <input type="hidden" name="customer_id" id="selectedCustomerId" value="">

            <div id="selectedCustomerBadge" class="hidden p-4 bg-emerald-50/60 rounded-2xl border border-emerald-200/80 mb-4 flex justify-between items-center">
                <div>
                    <span class="text-[10px] text-emerald-700 uppercase font-bold tracking-wider">Cliente Seleccionado</span>
                    <h4 id="selectedCustomerName" class="font-bold text-slate-900 text-sm mt-0.5"></h4>
                    <span id="selectedCustomerType" class="text-[10px] bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded-full font-black uppercase mt-1 inline-block"></span>
                </div>
                <button type="button" id="clearCustomerBtn" class="text-xs text-rose-600 hover:text-rose-800 font-bold p-1">
                    <i class="fa-solid fa-xmark mr-1"></i> Quitar
                </button>
            </div>

            <!-- Alta de cliente nuevo -->
            <div id="newCustomerFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50/80 p-5 rounded-2xl border border-slate-200 text-xs">
                <div class="md:col-span-2 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">
                    ¿Es cliente nuevo que compra por primera vez? Registrar datos:
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombres</label>
                    <input type="text" name="new_first_name" id="newFirstName" class="w-full p-2 bg-white border rounded-xl" placeholder="Ej. Juan">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Apellidos</label>
                    <input type="text" name="new_last_name" id="newLastName" class="w-full p-2 bg-white border rounded-xl" placeholder="Ej. Mamani Quispe">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">DNI / RUC (Opcional)</label>
                    <input type="text" name="new_dni_ruc" class="w-full p-2 bg-white border rounded-xl" placeholder="8 dígitos">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Categoría</label>
                    <select name="new_type" id="newCustomerTypeSelect" class="w-full p-2 bg-white border rounded-xl font-semibold">
                        <option value="local">Cliente Local (S/ 20)</option>
                        <option value="mayorista">Cliente Mayorista (S/ 19)</option>
                        <option value="proveedor">Proveedor de Huata (S/ 18)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- TARIFAS Y TOTAL SPARK STYLE -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Moldes de Queso</label>
                <input type="number" name="cheese_molds_quantity" id="quantityInput" min="1" max="{{ $stockQueso }}" value="1" required
                    class="w-full text-3xl font-black p-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-spark-lime">
                <span class="text-[10px] text-slate-400 mt-1 block">A partir de 10 quesos se aplica tarifa mayorista S/ 19.</span>
            </div>

            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 text-center">
                <span class="text-[10px] text-slate-400 font-bold uppercase block tracking-wider">Precio Unitario</span>
                <span class="text-3xl font-black text-slate-800 mt-1 block" id="unitPriceDisplay">S/ 20.00</span>
                <span id="priceReasonBadge" class="text-[10px] font-bold text-slate-500 uppercase mt-1 block">Tarifa local</span>
            </div>

            <div class="bg-spark-cardDark text-white p-5 rounded-2xl text-center shadow-sm">
                <span class="text-[10px] text-spark-lime font-bold uppercase block tracking-wider">Total a Cobrar (Efectivo)</span>
                <span class="text-3xl font-black text-white mt-1 block" id="totalDisplay">S/ 20.00</span>
                <span class="text-[10px] text-slate-300 font-medium mt-1 block"><i class="fa-solid fa-money-bill-wave text-spark-lime mr-1"></i> Contra entrega</span>
            </div>
        </div>

        <!-- MODALIDAD DE PAGO -->
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-2">Modalidad de Pago:</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-[#0f1713] transition">
                    <input type="radio" name="payment_method" value="efectivo" checked class="text-[#0f1713] focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block"><i class="fa-solid fa-money-bill-wave text-emerald-600 mr-1"></i> Pago en Efectivo</span>
                        <span class="text-[10px] text-slate-400">Cobro directo contra entrega en caja</span>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-[#0f1713] transition">
                    <input type="radio" name="payment_method" value="descuento_leche" class="text-[#0f1713] focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-emerald-800 block"><i class="fa-solid fa-cheese text-amber-500 mr-1"></i> A cuenta de Leche (Proveedor)</span>
                        <span class="text-[10px] text-slate-500">Se deduce automáticamente de su liquidación</span>
                    </div>
                </label>
            </div>
        </div>

        <button type="submit" class="w-full bg-spark-dark hover:bg-black text-spark-lime font-black py-3.5 rounded-2xl text-xs tracking-wider uppercase transition shadow-md flex items-center justify-center gap-2">
            <i class="fa-solid fa-receipt text-sm"></i>
            <span>Cobrar y Emitir Recibo de Venta</span>
        </button>
    </form>
</div>

@push('scripts')
<script>
    const searchInput = document.getElementById('customerSearch');
    const resultsBox = document.getElementById('searchResults');
    const selectedIdInput = document.getElementById('selectedCustomerId');
    const badge = document.getElementById('selectedCustomerBadge');
    const nameDisplay = document.getElementById('selectedCustomerName');
    const typeDisplay = document.getElementById('selectedCustomerType');
    const clearBtn = document.getElementById('clearCustomerBtn');
    const newFields = document.getElementById('newCustomerFields');

    const qtyInput = document.getElementById('quantityInput');
    const unitPriceSpan = document.getElementById('unitPriceDisplay');
    const totalSpan = document.getElementById('totalDisplay');
    const reasonBadge = document.getElementById('priceReasonBadge');

    let currentCustomer = null;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            resultsBox.classList.add('hidden');
            return;
        }

        fetch(`{{ route('ventas.customers.search') }}?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                resultsBox.innerHTML = '';
                if (data.length === 0) {
                    resultsBox.innerHTML = '<div class="p-3 text-xs text-slate-400">Sin coincidencias con ese apellido.</div>';
                } else {
                    data.forEach(cust => {
                        const item = document.createElement('div');
                        item.className = 'p-3 hover:bg-slate-50 cursor-pointer text-xs border-b last:border-0 flex justify-between items-center';
                        item.innerHTML = `
                            <div>
                                <strong class="text-slate-900">${cust.last_name}</strong>, ${cust.first_name}
                                <span class="text-slate-400 text-[10px]">(${cust.dni_ruc || 'Sin DNI'})</span>
                            </div>
                            <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">${cust.type}</span>
                        `;
                        item.onclick = () => selectCustomer(cust);
                        resultsBox.appendChild(item);
                    });
                }
                resultsBox.classList.remove('hidden');
            });
    });

    function selectCustomer(cust) {
        currentCustomer = cust;
        selectedIdInput.value = cust.id;
        nameDisplay.textContent = `${cust.last_name}, ${cust.first_name} (DNI: ${cust.dni_ruc || '—'})`;
        typeDisplay.textContent = cust.type;
        badge.classList.remove('hidden');
        newFields.classList.add('hidden');
        resultsBox.classList.add('hidden');
        searchInput.value = '';
        recalc();
    }

    clearBtn.addEventListener('click', function() {
        currentCustomer = null;
        selectedIdInput.value = '';
        badge.classList.add('hidden');
        newFields.classList.remove('hidden');
        recalc();
    });

    qtyInput.addEventListener('input', recalc);
    document.getElementById('newCustomerTypeSelect').addEventListener('change', recalc);

    function recalc() {
        const qty = parseInt(qtyInput.value) || 1;
        let price = 20.00;
        let reason = 'Cliente local regular';

        if (currentCustomer) {
            if (currentCustomer.type === 'proveedor' || currentCustomer.linked_user_id) {
                price = 18.00;
                reason = 'Tarifa Proveedor Huata (S/ 18)';
            } else if (currentCustomer.type === 'mayorista' || currentCustomer.is_wholesale_approved || qty >= 10) {
                price = 19.00;
                reason = qty >= 10 ? 'Mayorista (cantidad >= 10 moldes)' : 'Tarifa Mayorista Registrado';
            }
        } else {
            const manualType = document.getElementById('newCustomerTypeSelect').value;
            if (manualType === 'proveedor') {
                price = 18.00;
                reason = 'Proveedor nuevo';
            } else if (manualType === 'mayorista' || qty >= 10) {
                price = 19.00;
                reason = qty >= 10 ? 'Mayorista automático (>= 10 moldes)' : 'Mayorista nuevo';
            }
        }

        unitPriceSpan.textContent = `S/ ${price.toFixed(2)}`;
        totalSpan.textContent = `S/ ${(price * qty).toFixed(2)}`;
        reasonBadge.textContent = reason;
    }
</script>
@endpush
@endsection
