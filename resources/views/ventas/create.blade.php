@extends('layouts.app')

@section('title', 'Nueva Venta en Caja')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex justify-between items-center">
        <div>
            <span class="text-[10px] bg-emerald-100 text-emerald-900 font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Punto de Cobro</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Caja de Despacho</h1>
            <p class="text-xs text-slate-500">Búsqueda rápida por apellido o DNI y tarifa automática por producto.</p>
        </div>
        <div class="bg-slate-50 border border-slate-200 px-5 py-3 rounded-2xl text-right">
            <span class="text-[10px] text-slate-400 block uppercase font-bold">Productos con stock</span>
            <span class="text-2xl font-black text-slate-900 tracking-tight">
                {{ $productos->where('stock_actual', '>', 0)->count() }}
                <span class="text-xs text-slate-400 font-bold">de {{ $productos->count() }}</span>
            </span>
        </div>
    </div>

    @if($productos->where('stock_actual', '>', 0)->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-5 text-xs text-amber-800">
        No hay stock de ningún producto terminado. Cierra un lote en
        <a href="{{ route('produccion.lotes.index') }}" class="font-bold underline">Lotes de Producción</a> antes de vender.
    </div>
    @endif

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
                <div id="clienteConocido" class="hidden md:col-span-2 p-3 rounded-xl bg-blue-50 border border-blue-200 text-[11px] text-blue-900">
                    <strong>Ya está registrado.</strong>
                    <span id="clienteConocidoTexto"></span>
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">DNI / RUC (Opcional)</label>
                    <input type="text" name="new_dni_ruc" class="w-full p-2 bg-white border rounded-xl" placeholder="8 dígitos">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Categoría</label>
                    <select name="new_type" id="newCustomerTypeSelect" class="w-full p-2 bg-white border rounded-xl font-semibold">
                        @foreach($tiposCliente as $tipo)
                        <option value="{{ $tipo->slug }}" @selected($tipo->slug === 'local')>{{ $tipo->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- RENGLONES DE LA VENTA -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-basket-shopping text-spark-limeText"></i> Qué se despacha
                </h3>
                <button type="button" id="addLineBtn"
                    class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                    <i class="fa-solid fa-plus mr-1"></i> Agregar producto
                </button>
            </div>

            <div id="saleLines" class="space-y-2"></div>

            <p class="text-[10px] text-slate-400 mt-2">
                A partir de 10 unidades de un mismo producto se aplica su tarifa mayorista.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 text-center">
                <span class="text-[10px] text-slate-400 font-bold uppercase block tracking-wider">Unidades despachadas</span>
                <span class="text-3xl font-black text-slate-800 mt-1 block" id="unitsDisplay">0</span>
                <span id="priceReasonBadge" class="text-[10px] font-bold text-slate-500 uppercase mt-1 block">Tarifa local</span>
            </div>

            <div class="bg-spark-cardDark text-white p-5 rounded-2xl text-center shadow-sm">
                <span class="text-[10px] text-spark-lime font-bold uppercase block tracking-wider">Total a Cobrar</span>
                <span class="text-3xl font-black text-white mt-1 block" id="totalDisplay">S/ 0.00</span>
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

<template id="saleLineTemplate">
    <div class="grid grid-cols-12 gap-2 items-center" data-line>
        <select data-line-product required
            class="col-span-5 p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 text-xs">
        </select>
        <input type="number" step="0.01" min="0.01" value="1" required data-line-qty
            class="col-span-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-right font-bold text-xs">
        <span class="col-span-2 text-right text-xs text-slate-500">S/ <span data-line-price>0.00</span></span>
        <span class="col-span-2 text-right text-xs font-black text-slate-800">S/ <span data-line-subtotal>0.00</span></span>
        <button type="button" data-line-remove
            class="col-span-1 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[10px] uppercase">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</template>

@php
    // El catálogo que el mostrador necesita para tarifar sin ir al servidor:
    // cada producto con lo que le cobra a cada tipo de cliente.
    $catalogoParaCaja = $productos->map(fn ($producto) => [
        'id' => $producto->id,
        'name' => $producto->name,
        'unit' => $producto->unit,
        'stock' => (float) $producto->stock_actual,
        'precios' => $tiposCliente->mapWithKeys(fn ($tipo) => [
            $tipo->id => $tipo->priceFor($producto),
        ])->filter(fn ($precio) => $precio !== null),
    ])->values();

    $tipoPublico = \App\Models\ClientType::tipoPublico();

    $tiposParaCaja = $tiposCliente->map(fn ($tipo) => [
        'id' => $tipo->id,
        'name' => $tipo->name,
        'min_quantity' => (float) $tipo->min_quantity,
        'auto_role' => $tipo->auto_role,
        'slug' => $tipo->slug,
    ])->values();
@endphp

@push('scripts')
<script>
    const PRODUCTOS = @json($catalogoParaCaja);
    const TIPOS_CLIENTE = @json($tiposParaCaja);
    const TIPO_PUBLICO = @json($tipoPublico?->id);

    const searchInput = document.getElementById('customerSearch');
    const resultsBox = document.getElementById('searchResults');
    const selectedIdInput = document.getElementById('selectedCustomerId');
    const badge = document.getElementById('selectedCustomerBadge');
    const nameDisplay = document.getElementById('selectedCustomerName');
    const typeDisplay = document.getElementById('selectedCustomerType');
    const clearBtn = document.getElementById('clearCustomerBtn');
    const newFields = document.getElementById('newCustomerFields');

    const linesBox = document.getElementById('saleLines');
    const lineTemplate = document.getElementById('saleLineTemplate');
    const unitsSpan = document.getElementById('unitsDisplay');
    const totalSpan = document.getElementById('totalDisplay');
    const reasonBadge = document.getElementById('priceReasonBadge');

    let currentCustomer = null;

    // --- Cliente ---
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

    document.getElementById('newCustomerTypeSelect').addEventListener('change', recalc);

    // --- Reconocer a quien ya compró antes ---
    // Si el nombre que se está tecleando ya existe, se usa esa ficha: conserva
    // su tarifa y no queda duplicado en el padrón de compradores.
    const nombreInput = document.getElementById('newFirstName');
    const apellidoInput = document.getElementById('newLastName');
    const avisoConocido = document.getElementById('clienteConocido');
    const avisoTexto = document.getElementById('clienteConocidoTexto');
    let temporizador = null;

    function buscarConocido() {
        const apellidos = apellidoInput.value.trim();

        if (currentCustomer || apellidos.length < 3) {
            avisoConocido.classList.add('hidden');
            return;
        }

        const parametros = new URLSearchParams({
            last_name: apellidos,
            first_name: nombreInput.value.trim(),
        });

        fetch(`{{ route('ventas.customers.match') }}?${parametros}`)
            .then(res => res.json())
            .then(cliente => {
                if (!cliente) {
                    avisoConocido.classList.add('hidden');
                    return;
                }

                avisoTexto.textContent =
                    ` ${cliente.last_name}, ${cliente.first_name} — se le aplicará su tarifa de ${cliente.type}.`;
                avisoConocido.classList.remove('hidden');

                // Se adopta su ficha: la caja recalcula con la tarifa que le toca.
                currentCustomer = cliente;
                recalc();
            });
    }

    [nombreInput, apellidoInput].forEach(function (campo) {
        campo.addEventListener('input', function () {
            // Al reescribir el nombre se suelta la ficha adoptada.
            if (currentCustomer && !selectedIdInput.value) {
                currentCustomer = null;
            }

            clearTimeout(temporizador);
            temporizador = setTimeout(buscarConocido, 350);
        });
    });

    // --- Renglones ---
    // Mismas tres reglas que ClientType::paraCliente() en el servidor: rol del
    // padrón, tipo asignado, y tramo por cantidad. Si esto y el servidor no
    // dijeran lo mismo, la caja mostraría un total y cobraría otro.
    function tipoParaLaVenta(cantidad) {
        if (currentCustomer?.linked_role) {
            const porRol = TIPOS_CLIENTE.find(t => t.auto_role === currentCustomer.linked_role);
            if (porRol) { return porRol; }
        }

        const candidatos = TIPOS_CLIENTE.filter(
            t => t.auto_role === null && t.min_quantity <= Math.ceil(cantidad)
        );

        const asignado = currentCustomer
            ? TIPOS_CLIENTE.find(t => t.id === currentCustomer.client_type_id)
                ?? TIPOS_CLIENTE.find(t => t.slug === currentCustomer.type)
            : TIPOS_CLIENTE.find(t => t.slug === document.getElementById('newCustomerTypeSelect').value);

        if (asignado) { candidatos.push(asignado); }

        return candidatos.sort((a, b) => b.min_quantity - a.min_quantity)[0] ?? TIPOS_CLIENTE[0];
    }

    function precioDe(producto, cantidad) {
        const tipo = tipoParaLaVenta(cantidad);

        if (!tipo) { return { precio: 0, motivo: 'Sin tipo de cliente configurado' }; }

        // Sin tarifa para ese tipo se cobra la del público, igual que el servidor.
        const propia = producto.precios[tipo.id];
        const publica = producto.precios[TIPO_PUBLICO];

        if (propia === undefined && publica === undefined) {
            return { precio: 0, motivo: 'Este producto no tiene tarifa cargada' };
        }

        return {
            precio: propia ?? publica,
            motivo: propia !== undefined ? tipo.name : tipo.name + ' (tarifa de público)',
        };
    }

    function renumerar() {
        linesBox.querySelectorAll('[data-line]').forEach(function (fila, indice) {
            fila.querySelector('[data-line-product]').name = `items[${indice}][product_id]`;
            fila.querySelector('[data-line-qty]').name = `items[${indice}][quantity]`;
        });
    }

    function recalc() {
        let total = 0;
        let unidades = 0;
        let ultimoMotivo = 'Tarifa local';

        linesBox.querySelectorAll('[data-line]').forEach(function (fila) {
            const select = fila.querySelector('[data-line-product]');
            const producto = PRODUCTOS.find(p => p.id === parseInt(select.value));
            const qtyInput = fila.querySelector('[data-line-qty]');
            const cantidad = parseFloat(qtyInput.value) || 0;

            if (!producto) { return; }

            qtyInput.max = producto.stock;
            qtyInput.classList.toggle('ring-2', cantidad > producto.stock);
            qtyInput.classList.toggle('ring-rose-400', cantidad > producto.stock);

            const { precio, motivo } = precioDe(producto, cantidad);
            const subtotal = precio * cantidad;

            fila.querySelector('[data-line-price]').textContent = precio.toFixed(2);
            fila.querySelector('[data-line-subtotal]').textContent = subtotal.toFixed(2);

            total += subtotal;
            unidades += cantidad;
            ultimoMotivo = motivo;
        });

        unitsSpan.textContent = unidades.toFixed(2).replace(/\.?0+$/, '');
        totalSpan.textContent = `S/ ${total.toFixed(2)}`;
        reasonBadge.textContent = ultimoMotivo;
    }

    function agregarRenglon() {
        const fila = lineTemplate.content.firstElementChild.cloneNode(true);
        const select = fila.querySelector('[data-line-product]');

        PRODUCTOS.forEach(function (producto) {
            const opcion = document.createElement('option');
            const saldo = producto.stock.toFixed(2).replace(/\.?0+$/, '');
            opcion.value = producto.id;
            opcion.textContent = `${producto.name} — ${saldo} ${producto.unit} en almacén`;
            opcion.disabled = producto.stock <= 0;
            select.appendChild(opcion);
        });

        // Arranca en el primer producto que de verdad se puede despachar.
        const disponible = PRODUCTOS.find(p => p.stock > 0);
        if (disponible) { select.value = disponible.id; }

        select.addEventListener('change', recalc);
        fila.querySelector('[data-line-qty]').addEventListener('input', recalc);

        fila.querySelector('[data-line-remove]').addEventListener('click', function () {
            // Una venta sin renglones no existe: el último no se quita.
            if (linesBox.querySelectorAll('[data-line]').length > 1) {
                fila.remove();
                renumerar();
                recalc();
            }
        });

        linesBox.appendChild(fila);
        renumerar();
        recalc();
    }

    document.getElementById('addLineBtn').addEventListener('click', agregarRenglon);

    agregarRenglon();
</script>
@endpush
@endsection
