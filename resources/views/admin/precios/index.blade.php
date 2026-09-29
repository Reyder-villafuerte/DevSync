@extends('layouts.app')

@section('title', 'Configuración de Precios y Temporada')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Tarifas de acopio"
        descripcion="Lo que la planta le paga al productor por unidad. La tarifa base rige cuando nada la castiga; las demás tienen una condición sobre una medida de calidad. Si aplican varias, gana la de mayor prioridad."
        :coleccion="$reglas"
        :columnas="6"
        vacio="No hay tarifas de acopio cargadas.">

        <x-slot:acciones>
            <button type="button" data-nueva-tarifa
                class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Agregar
            </button>
        </x-slot:acciones>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Se acopia</th>
            <th class="text-left py-3 px-4 font-bold">Tarifa</th>
            <th class="text-left py-3 px-4 font-bold">Cuándo aplica</th>
            <th class="text-right py-3 px-4 font-bold">Precio</th>
            <th class="text-center py-3 px-4 font-bold">Todo el ciclo</th>
            <th class="text-right py-3 px-4 font-bold">Prioridad</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($reglas as $regla)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $regla->id }}</td>
            <td class="py-3 px-4 text-slate-600">{{ $regla->supply->name }}</td>
            <td class="py-3 px-4 font-bold {{ $regla->is_active ? 'text-slate-900' : 'text-slate-400 line-through' }}">
                {{ $regla->name }}
            </td>
            <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">{{ $regla->etiquetaCondicion() }}</td>
            <td class="py-3 px-4 text-right font-black text-[#0f1713]">S/ {{ number_format($regla->price_per_unit, 2) }}</td>
            <td class="py-3 px-4 text-center">
                @if($regla->applies_to_whole_cycle)
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">Sí</span>
                @else
                <span class="text-slate-300">—</span>
                @endif
            </td>
            <td class="py-3 px-4 text-right text-slate-500">{{ $regla->priority }}</td>
            <td class="py-3 px-4">
                <div class="flex justify-end gap-1.5">
                    <button type="button" data-editar-tarifa
                        data-id="{{ $regla->id }}"
                        data-insumo="{{ $regla->supply_id }}"
                        data-nombre="{{ $regla->name }}"
                        data-metrica="{{ $regla->metric }}"
                        data-operador="{{ $regla->operator }}"
                        data-umbral="{{ $regla->threshold }}"
                        data-precio="{{ number_format($regla->price_per_unit, 2, '.', '') }}"
                        data-ciclo="{{ $regla->applies_to_whole_cycle ? '1' : '0' }}"
                        data-penalidad="{{ $regla->penalty_type }}"
                        data-prioridad="{{ $regla->priority }}"
                        data-activo="{{ $regla->is_active ? '1' : '0' }}"
                        data-base="{{ $regla->esBase() ? '1' : '0' }}"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        Editar
                    </button>
                    @unless($regla->esBase())
                    <form action="{{ route('admin.precios.tarifas.destroy', $regla) }}" method="POST"
                        onsubmit="return confirm('¿Eliminar la tarifa {{ $regla->name }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[10px] uppercase">
                            Eliminar
                        </button>
                    </form>
                    @endunless
                </div>
            </td>
        </tr>
        @endforeach
    </x-tabla>

    <x-tabla
        titulo="Historial de temporadas"
        descripcion="Cada cierre guarda una foto de lo que regía ese día. Ni la leche ni el queso se tarifan aquí: la leche se administra arriba y el queso en Productos y Recetas."
        :coleccion="$history"
        :columnas="5"
        vacio="Todavía no se cerró ninguna temporada.">

        <x-slot:acciones>
            <button type="button" data-cerrar-temporada
                class="px-4 py-2.5 rounded-xl bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Cerrar temporada
            </button>
        </x-slot:acciones>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Temporada</th>
            <th class="text-right py-3 px-4 font-bold">Leche base</th>
            <th class="text-right py-3 px-4 font-bold">Queso (prov/may/local)</th>
            <th class="text-left py-3 px-4 font-bold">Cerrada</th>
            <th class="text-left py-3 px-4 font-bold">Nota</th>
        </x-slot:encabezados>

        @foreach($history as $item)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $item->id }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">
                {{ $item->season_name }}
                @if($item->is_active)
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-lime-100 text-spark-limeText ml-1">Vigente</span>
                @endif
            </td>
            <td class="py-3 px-4 text-right font-mono text-slate-700">S/ {{ number_format($item->price_milk_base, 2) }}</td>
            <td class="py-3 px-4 text-right font-mono text-slate-500">
                {{ number_format($item->price_cheese_provider, 2) }} / {{ number_format($item->price_cheese_wholesale, 2) }} / {{ number_format($item->price_cheese_local, 2) }}
            </td>
            <td class="py-3 px-4 text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</td>
            <td class="py-3 px-4 text-slate-400 max-w-xs truncate">{{ $item->notes }}</td>
        </tr>
        @endforeach
    </x-tabla>

</div>

{{-- Modal: cerrar temporada --}}
<div id="modalTemporada" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Cerrar temporada</h3>
                <p class="text-[11px] text-slate-500">Guarda una foto de las tarifas que rigen hoy.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form action="{{ route('admin.precios.update') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre de la temporada</label>
                <input type="text" name="season_name" value="{{ old('season_name', $currentPrice->season_name) }}" required
                    placeholder="Ej. Temporada Seca 2026"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Justificación (opcional)</label>
                <textarea name="notes" rows="2" placeholder="Ej. Ajuste por escasez de pastos"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800"></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Cerrar temporada</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalTemporada');

    document.querySelectorAll('[data-cerrar-temporada]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = false; });
    });

    modal.querySelectorAll('[data-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = true; });
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) { modal.hidden = true; }
    });
})();
</script>

{{-- Modal: crear o editar una tarifa de acopio --}}
<div id="modalTarifa" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900" data-titulo>Nueva tarifa de acopio</h3>
                <p class="text-[11px] text-slate-500">Ejemplos: tarifa base, penalidad por agua, bonificación por leche fría.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.precios.tarifas.store') }}" class="space-y-3 text-xs" data-formulario
            data-url-crear="{{ route('admin.precios.tarifas.store') }}"
            data-url-editar="{{ route('admin.precios.tarifas.update', ['regla' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" data-metodo>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Qué se acopia</label>
                <select name="supply_id" data-campo="supply_id" required
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                    @foreach($insumosAcopiados as $insumo)
                    <option value="{{ $insumo->id }}">{{ $insumo->name }} ({{ $insumo->unit }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre de la tarifa</label>
                    <input type="text" name="name" required maxlength="80" data-campo="name" placeholder="Ej. Bonificación leche fría"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Precio por unidad S/</label>
                    <input type="number" step="0.01" min="0" name="price_per_unit" required data-campo="price_per_unit"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
            </div>

            <div class="border border-slate-200 rounded-2xl p-3 space-y-3">
                <span class="text-[10px] uppercase font-bold text-slate-600 block">Cuándo aplica</span>
                <p class="text-[10px] text-slate-400">
                    Déjalo vacío para la <strong>tarifa base</strong>: la que rige cuando ninguna otra aplica.
                    Solo puede haber una por producto.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <select name="metric" data-campo="metric"
                        class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        <option value="">Sin condición (base)</option>
                        <option value="water_addition_percentage">% de agua añadida</option>
                        <option value="fat_percentage">% de grasa</option>
                        <option value="snf_percentage">% de sólidos no grasos</option>
                        <option value="density">Densidad</option>
                        <option value="temperature">Temperatura</option>
                    </select>
                    <select name="operator" data-campo="operator"
                        class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        @foreach(\App\Models\CollectionPriceRule::OPERADORES as $operador)
                        <option value="{{ $operador }}">{{ $operador }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" name="threshold" data-campo="threshold" placeholder="Valor"
                        class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Prioridad</label>
                    <input type="number" min="0" max="999" name="priority" value="0" data-campo="priority"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                    <span class="text-[10px] text-slate-400 mt-1 block">Si aplican varias, gana la más alta.</span>
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Etiqueta (opcional)</label>
                    <select name="penalty_type" data-campo="penalty_type"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        <option value="">Ninguna</option>
                        <option value="leve_descuento">Penalidad leve</option>
                        <option value="grave_expulsion">Penalidad grave (riesgo de expulsión)</option>
                    </select>
                </div>
            </div>

            <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600">
                <input type="checkbox" name="applies_to_whole_cycle" value="1" data-campo="applies_to_whole_cycle">
                Se aplica a todo el ciclo de pago, no solo al día que se detectó
            </label>

            <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600" data-solo-edicion>
                <input type="checkbox" name="is_active" value="1" data-campo="is_active" checked>
                Tarifa activa
            </label>

            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalTarifa');
    const form = modal.querySelector('[data-formulario]');

    function abrir(valores, id) {
        modal.querySelector('[data-titulo]').textContent = id ? 'Editar tarifa de acopio' : 'Nueva tarifa de acopio';
        form.action = id ? form.dataset.urlEditar.replace('__ID__', id) : form.dataset.urlCrear;
        form.querySelector('[data-metodo]').value = id ? 'PUT' : 'POST';

        form.querySelectorAll('[data-campo]').forEach(function (campo) {
            const valor = valores[campo.dataset.campo];

            if (campo.type === 'checkbox') {
                campo.checked = campo.dataset.campo === 'is_active' ? (id ? valor === '1' : true) : valor === '1';
            } else {
                campo.value = valor ?? '';
            }
        });

        form.querySelectorAll('[data-solo-edicion]').forEach(function (bloque) {
            bloque.hidden = !id;
        });

        modal.hidden = false;
        form.querySelector('[data-campo="name"]').focus();
    }

    document.querySelectorAll('[data-nueva-tarifa]').forEach(function (boton) {
        boton.addEventListener('click', function () { abrir({ priority: '0' }, null); });
    });

    document.querySelectorAll('[data-editar-tarifa]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrir({
                supply_id: boton.dataset.insumo,
                name: boton.dataset.nombre,
                metric: boton.dataset.metrica,
                operator: boton.dataset.operador,
                threshold: boton.dataset.umbral,
                price_per_unit: boton.dataset.precio,
                applies_to_whole_cycle: boton.dataset.ciclo,
                penalty_type: boton.dataset.penalidad,
                priority: boton.dataset.prioridad,
                is_active: boton.dataset.activo,
            }, boton.dataset.id);
        });
    });

    modal.querySelectorAll('[data-cerrar]').forEach(function (boton) {
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
@endsection
