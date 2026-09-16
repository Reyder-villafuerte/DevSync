<x-page-header :title="'Hola, '.$user->nombres" :subtitle="$user->roles->first()->name" eyebrow="TU JORNADA EN MILKFLOW">
    <x-badge>{{ now()->format('d/m/Y') }}</x-badge>
</x-page-header>
@if(in_array($slug,['acopiador','produccion']))
<x-card class="sync-line">
    <div>
        <h3>Estado de sincronización</h3><x-badge :state="$pending?'PENDIENTE':'ACTIVO'">{{ $pending?$pending.' registros pendientes':'Sin registros pendientes' }}</x-badge>
    </div>
    <a href="/{{ $slug }}/sync" class="table-action">Ver sincronización →</a>
</x-card>
@endif
@foreach($routes as $route)
<p class="mt-3"><b>Ruta asignada: {{ $route->nombre }}</b> · {{ $route->vehiculo }}<br><span class="muted">Sectores: {{ $route->sectores->pluck('nombre')->join(', ') }}</span></p>
@endforeach
<div class="section-title">
    <h2>Resumen {{ $slug==='productor'?'de la semana':'del día' }}</h2><span class="muted">{{ $slug==='productor'?$start->format('d/m').' — '.$end->format('d/m'):now()->format('d/m/Y') }}</span>
</div>
<div class="stats {{ $slug==='admin'?'admin-stats':'' }}">
    @foreach($stats as $label=>$value)
    <x-stat-card :label="$label" :value="is_numeric($value)?number_format($value,is_float($value)||str_contains((string)$value,'.')?2:0):$value" />
    @endforeach
</div>
<div class="content-grid">
    <section>
        <h2>Accesos rápidos</h2>
        <x-button :href="'/'.$slug.'/'.$primary[0]" class="w-full mt-3 mb-3">{{ $primary[1] }}</x-button>
        <div class="action-grid">
            @foreach(array_slice(App\Services\Navigation::menus()[$slug],1,6,true) as $path=>$label)
            @if($path!==$primary[0] && $path!=='ruta/cerrar')<x-button variant="secondary" :href="'/'.$slug.'/'.$path">{{ $label }}</x-button>@endif
            @endforeach
        </div>
        @if($slug==='acopiador')<x-button variant="secondary" href="/acopiador/ruta/cerrar" class="w-full mt-3">Cerrar Ruta y Descargar</x-button>@endif
    </section>
    <section>
        <h2>{{ $slug==='supervisor'?'Inspecciones recientes':'Registros recientes' }}</h2>
        <x-card class="mt-3">
            @forelse($recent as $item)
            <a href="/{{ $slug }}/{{ $module }}/{{ $item->id }}" class="recent-row">
                <b>{{ $item->codigo_lote ?? $item->cliente ?? $item->tipo ?? 'Inspección #'.$item->id }}</b>
                <span>{{ $item->litros ?? $item->litros_leche ?? $item->total ?? $item->resultado }}{{ in_array($module,['entregas','lotes'])?' L':'' }}</span>
                <small class="muted">{{ $item->fecha_hora ?? $item->fecha }}</small>
            </a>
            @empty<div class="empty">Sin registros por el momento.<br>La actividad aparecerá aquí.</div>@endforelse
        </x-card>
    </section>
</div>
