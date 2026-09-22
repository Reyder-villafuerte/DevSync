@extends('layouts.app')

@section('title', 'Lotes de Producción')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Lotes abiertos"
        descripcion="Al iniciar el lote se descuentan los ingredientes de la receta. El producto terminado entra al almacén recién cuando el proceso cumple sus horas."
        :coleccion="$abiertos"
        :columnas="5"
        vacio="No hay lotes planificados ni en proceso.">

        <x-slot:acciones>
            @if($puedeOperar && $productos->isNotEmpty())
            <button type="button" data-nuevo-lote
                class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Agregar
            </button>
            @endif
        </x-slot:acciones>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Lote</th>
            <th class="text-left py-3 px-4 font-bold">Qué se produce</th>
            <th class="text-left py-3 px-4 font-bold">Consumió</th>
            <th class="text-left py-3 px-4 font-bold">Estado</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($abiertos as $orden)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition align-top">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $orden->id }}</td>
            <td class="py-3 px-4">
                <span class="font-mono text-[11px] text-slate-600">{{ $orden->batch_number }}</span>
                <span class="block text-[10px] text-slate-400">{{ $orden->supervisor->name }}</span>
            </td>
            <td class="py-3 px-4 font-bold text-slate-900">
                {{ number_format($orden->planned_quantity, 2) }} {{ $orden->product->unit }}
                de {{ $orden->product->name }}
                @if($orden->notes)
                <span class="block text-[10px] font-normal text-slate-400 italic">{{ $orden->notes }}</span>
                @endif
            </td>
            <td class="py-3 px-4 text-slate-600">
                @forelse($orden->items as $item)
                <div class="text-[11px]">
                    {{ rtrim(rtrim(number_format($item->quantity_used, 4, '.', ''), '0'), '.') }}
                    {{ $item->unit }} de <strong>{{ $item->nombreIngrediente() }}</strong>
                </div>
                @empty
                <span class="text-slate-300 italic">Todavía nada</span>
                @endforelse
            </td>
            <td class="py-3 px-4">
                @if($orden->status === 'en_proceso')
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">En proceso</span>
                @else
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Planificada</span>
                @endif
                @if($orden->started_at)
                <span class="block text-[10px] text-slate-400 mt-1">Inició {{ $orden->started_at->format('d/m H:i') }}</span>
                @endif
                @if($orden->expected_ready_at)
                <span class="block text-[10px] text-slate-400">Listo {{ $orden->expected_ready_at->format('d/m H:i') }}</span>
                @endif
            </td>
            <td class="py-3 px-4">
                <div class="flex flex-wrap justify-end items-start gap-2">
                    @if(! $puedeOperar)
                    @if($orden->status === 'planificada')
                    <span class="px-3 py-2 rounded-xl bg-slate-100 text-slate-500 font-bold text-[10px] uppercase whitespace-nowrap">Sin iniciar</span>
                    @elseif($orden->isReady())
                    <span class="px-3 py-2 rounded-xl bg-lime-100 text-spark-limeText font-bold text-[10px] uppercase whitespace-nowrap">Listo para cerrar</span>
                    @else
                    <span class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-[10px] uppercase whitespace-nowrap">
                        Faltan {{ $orden->minutesRemaining() }} min
                    </span>
                    @endif
                    @else
                    @if($orden->status === 'planificada')
                    <form action="{{ route('produccion.lotes.iniciar', $orden) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded-xl bg-spark-dark text-spark-lime font-bold text-[10px] uppercase">Iniciar</button>
                    </form>
                    @elseif($orden->isReady())
                    <form action="{{ route('produccion.lotes.terminar', $orden) }}" method="POST" class="flex items-end gap-2">
                        @csrf
                        <input type="number" step="0.01" min="0.01" name="produced_quantity" value="{{ $orden->planned_quantity }}"
                            title="Salió realmente"
                            class="w-24 p-2 bg-slate-50 border border-slate-200 rounded-xl text-right font-semibold">
                        <button type="submit" class="px-3 py-2 rounded-xl bg-spark-dark text-spark-lime font-bold text-[10px] uppercase">Terminar</button>
                    </form>
                    @else
                    <span class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-[10px] uppercase whitespace-nowrap">
                        Faltan {{ $orden->minutesRemaining() }} min
                    </span>
                    @endif

                    <form action="{{ route('produccion.lotes.cancelar', $orden) }}" method="POST"
                        onsubmit="return confirm('¿Cancelar el lote {{ $orden->batch_number }}? Se devolverán los ingredientes.');">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded-xl bg-rose-50 text-rose-600 font-bold text-[10px] uppercase">Cancelar</button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </x-tabla>

    <x-tabla
        titulo="Historial de lotes"
        descripcion="Lo que ya se cerró o se canceló."
        :coleccion="$cerrados"
        :columnas="6"
        vacio="Ningún lote coincide con el filtro.">

        <x-slot:filtros>
            <form method="GET" action="{{ route('produccion.lotes.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Número de lote..."
                        class="w-52 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <select name="estado" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Terminados y cancelados</option>
                    <option value="terminada" @selected(request('estado') === 'terminada')>Solo terminados</option>
                    <option value="cancelada" @selected(request('estado') === 'cancelada')>Solo cancelados</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                @if(request('buscar') || request('estado'))
                <a href="{{ route('produccion.lotes.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtros</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Lote</th>
            <th class="text-left py-3 px-4 font-bold">Producto</th>
            <th class="text-right py-3 px-4 font-bold">Planificado</th>
            <th class="text-right py-3 px-4 font-bold">Salió</th>
            <th class="text-left py-3 px-4 font-bold">Cerrado</th>
            <th class="text-right py-3 px-4 font-bold">Estado</th>
        </x-slot:encabezados>

        @foreach($cerrados as $orden)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $orden->id }}</td>
            <td class="py-3 px-4 font-mono text-[11px] text-slate-600">{{ $orden->batch_number }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">{{ $orden->product->name }}</td>
            <td class="py-3 px-4 text-right text-slate-500">{{ number_format($orden->planned_quantity, 2) }}</td>
            <td class="py-3 px-4 text-right font-black text-slate-800">
                {{ $orden->produced_quantity === null ? '—' : number_format($orden->produced_quantity, 2) }}
            </td>
            <td class="py-3 px-4 text-slate-500">{{ $orden->finished_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td class="py-3 px-4 text-right">
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $orden->status === 'terminada' ? 'bg-lime-100 text-spark-limeText' : 'bg-rose-50 text-rose-600' }}">
                    {{ $orden->status }}
                </span>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>

@if($puedeOperar && $productos->isNotEmpty())
{{-- Modal: nuevo lote --}}
<div id="modalLote" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-3xl rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Nuevo lote</h3>
                <p class="text-[11px] text-slate-500">Agrega uno o varios productos y cuánto vas a producir de cada uno.</p>
            </div>
            <button type="button" data-cerrar-lote class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>


    @if($productos->isEmpty())
    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-2xl p-3">
        No hay productos activos. Crea uno en
        <a href="{{ route('produccion.productos.index') }}" class="underline font-bold">Productos y recetas</a>.
    </p>
    @else
    <form action="{{ route('produccion.lotes.store') }}" method="POST" id="form-lotes" class="text-xs">
        @csrf

        <div id="lotes-carrito" class="space-y-2 mb-3"></div>

        <button type="button" id="btn-agregar-linea"
            class="w-full mb-4 border-2 border-dashed border-slate-300 hover:border-spark-dark text-slate-500 hover:text-spark-dark font-bold py-2.5 rounded-xl text-[11px] uppercase tracking-wider transition">
            + Agregar producto
        </button>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end border-t border-slate-100 pt-4">
            <div class="md:col-span-8">
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Notas (opcional)</label>
                <input type="text" name="notes" maxlength="1000" value="{{ old('notes') }}"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800">
            </div>
            <div class="md:col-span-4 space-y-2">
                <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600">
                    <input type="checkbox" name="iniciar_ahora" value="1" checked> Iniciar ya
                </label>
                <button type="submit" class="w-full bg-spark-dark hover:bg-black text-spark-lime font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">
                    Registrar lote(s)
                </button>
            </div>
        </div>
    </form>

    {{-- Plantilla de un renglón del carrito --}}
    <template id="tpl-linea-lote">
        <div class="linea-lote grid grid-cols-1 md:grid-cols-12 gap-3 items-end bg-slate-50 border border-slate-200 rounded-2xl p-3">
            <div class="md:col-span-5">
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Producto</label>
                <select data-role="producto"
                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-semibold text-slate-800">
                    <option value="">Selecciona&hellip;</option>
                    @foreach($productos as $producto)
                    <option value="{{ $producto->id }}">{{ $producto->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Cantidad</label>
                <input type="number" step="0.01" min="0.01" data-role="cantidad"
                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-black text-slate-900">
            </div>

            <div class="md:col-span-3">
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Todavía alcanza para</label>
                <p data-role="stock" class="p-2.5 rounded-xl bg-white border border-slate-200 font-bold text-slate-500 leading-tight">
                    &hellip;
                </p>
            </div>

            <div class="md:col-span-1 flex justify-center">
                <button type="button" data-role="quitar"
                    class="w-full h-[42px] flex items-center justify-center rounded-xl bg-red-50 hover:bg-red-100 text-red-600 font-black text-sm">
                    &times;
                </button>
            </div>
        </div>
    </template>

    <script>
    (function () {
        const carrito = document.getElementById('lotes-carrito');
        const tpl = document.getElementById('tpl-linea-lote');
        const btnAgregar = document.getElementById('btn-agregar-linea');
        const form = document.getElementById('form-lotes');

        // La receta de cada producto y lo que hay hoy de cada ingrediente.
        const RECETAS = @json($recetas);
        const STOCK = @json($stockIngredientes);

        let index = 0;

        function numero(valor) {
            return new Intl.NumberFormat('es', { maximumFractionDigits: 2 }).format(valor);
        }

        /**
         * Cuánto se puede producir de ese producto con el pozo que se le pasa.
         * Devuelve también cuál es el ingrediente que lo está limitando, que es
         * lo que de verdad le interesa a quien arma la tanda.
         */
        function alcanzaPara(productoId, pozo) {
            const receta = RECETAS[productoId];

            if (!receta || !receta.ingredientes.length) {
                return { cantidad: 0, limita: null, unidad: '' };
            }

            let cantidad = Infinity;
            let limita = null;

            receta.ingredientes.forEach(function (ingrediente) {
                if (ingrediente.porUnidad <= 0) { return; }

                const hay = pozo[ingrediente.clave] ?? 0;
                const cabe = Math.floor((hay / ingrediente.porUnidad) * 100) / 100;

                if (cabe < cantidad) {
                    cantidad = cabe;
                    limita = ingrediente.nombre;
                }
            });

            return {
                cantidad: cantidad === Infinity ? 0 : Math.max(0, cantidad),
                limita: limita,
                unidad: receta.unidad,
            };
        }

        /** Lo que se lleva un renglón de cada ingrediente. */
        function consumoDe(linea) {
            const productoId = linea.querySelector('[data-role="producto"]').value;
            const cantidad = parseFloat(linea.querySelector('[data-role="cantidad"]').value) || 0;
            const receta = RECETAS[productoId];
            const consumo = {};

            if (!receta || cantidad <= 0) { return consumo; }

            receta.ingredientes.forEach(function (ingrediente) {
                consumo[ingrediente.clave] = (consumo[ingrediente.clave] ?? 0) + ingrediente.porUnidad * cantidad;
            });

            return consumo;
        }

        /**
         * Reparte el almacén entre los renglones.
         *
         * A cada renglón se le muestra lo que queda DESPU~ES de lo que se
         * llevan los demás. Es el caso de los 60 litros: si el queso se lleva
         * 50, el yogur tiene que ver 10, no 60 otra vez.
         */
        function repartir() {
            const lineas = Array.from(carrito.querySelectorAll('.linea-lote'));

            lineas.forEach(function (linea) {
                const select = linea.querySelector('[data-role="producto"]');
                const cantidad = linea.querySelector('[data-role="cantidad"]');
                const stockEl = linea.querySelector('[data-role="stock"]');

                stockEl.classList.remove('text-slate-500', 'text-red-600', 'text-emerald-600');

                if (!select.value) {
                    stockEl.innerHTML = '&hellip;';
                    stockEl.classList.add('text-slate-500');
                    delete cantidad.dataset.limite;
                    return;
                }

                // El pozo que le queda a ESTE renglón.
                const pozo = Object.assign({}, STOCK);

                lineas.forEach(function (otra) {
                    if (otra === linea) { return; }

                    const consumo = consumoDe(otra);

                    Object.keys(consumo).forEach(function (clave) {
                        pozo[clave] = (pozo[clave] ?? 0) - consumo[clave];
                    });
                });

                const alcance = alcanzaPara(select.value, pozo);

                stockEl.innerHTML = numero(alcance.cantidad) + ' ' + alcance.unidad
                    + (alcance.limita ? '<span class="block text-[9px] font-medium text-slate-400">por ' + alcance.limita + '</span>' : '');
                stockEl.classList.add(alcance.cantidad > 0 ? 'text-emerald-600' : 'text-red-600');

                cantidad.dataset.limite = alcance.cantidad;

                const pedido = parseFloat(cantidad.value) || 0;
                cantidad.classList.toggle('ring-2', pedido > alcance.cantidad);
                cantidad.classList.toggle('ring-red-400', pedido > alcance.cantidad);
            });
        }

        function agregarLinea() {
            const nodo = tpl.content.firstElementChild.cloneNode(true);
            const select = nodo.querySelector('[data-role="producto"]');
            const cantidad = nodo.querySelector('[data-role="cantidad"]');

            select.name = 'items[' + index + '][product_id]';
            cantidad.name = 'items[' + index + '][planned_quantity]';
            index++;

            select.addEventListener('change', repartir);
            cantidad.addEventListener('input', repartir);

            nodo.querySelector('[data-role="quitar"]').addEventListener('click', function () {
                nodo.remove();

                if (!carrito.querySelector('.linea-lote')) { agregarLinea(); }

                repartir();
            });

            carrito.appendChild(nodo);
            repartir();
        }

        btnAgregar.addEventListener('click', agregarLinea);

        form.addEventListener('submit', function (evento) {
            const lineas = Array.from(carrito.querySelectorAll('.linea-lote'));
            const cargadas = lineas.filter(function (linea) {
                return linea.querySelector('[data-role="producto"]').value
                    && parseFloat(linea.querySelector('[data-role="cantidad"]').value) > 0;
            });

            if (!cargadas.length) {
                evento.preventDefault();
                alert('Agrega al menos un producto al lote.');

                return;
            }

            const pasado = cargadas.find(function (linea) {
                const cantidad = linea.querySelector('[data-role="cantidad"]');

                return parseFloat(cantidad.value) > parseFloat(cantidad.dataset.limite || 0);
            });

            if (pasado) {
                evento.preventDefault();
                alert('No alcanza el almacén para toda la tanda. Revisa las cantidades en rojo.');

                return;
            }

            // Los renglones a medio llenar no se mandan.
            lineas.forEach(function (linea) {
                if (cargadas.includes(linea)) { return; }

                linea.querySelectorAll('select, input').forEach(function (campo) {
                    campo.disabled = true;
                });
            });
        });

        agregarLinea();
    })();
    </script>
    @endif
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalLote');

    document.querySelectorAll('[data-nuevo-lote]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = false; });
    });

    document.querySelectorAll('[data-cerrar-lote]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = true; });
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) { modal.hidden = true; }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') { modal.hidden = true; }
    });

    @if($errors->any())
    modal.hidden = false;
    @endif
})();
</script>
@endif
@endsection
