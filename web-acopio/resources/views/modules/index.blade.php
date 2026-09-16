@extends('layouts.app',['title'=>$definition['title']])
@section('content')<div class="page-header">
    <div><span class="eyebrow">MILKFLOW · {{ auth()->user()->roles->first()->name }}</span>
        <h1>{{ $definition['title'] }}</h1>
        <p class="muted">{{ $rows->total() }} registros</p>
    </div>@if($canCreate)<a class="mf-button" href="{{ $base }}/create">+ {{ $module==='liquidaciones'?'Calcular liquidación':'Nuevo registro' }}</a>@endif
</div>
<div class="mf-card table-wrap">
    <table>
        <thead>
            <tr>@foreach($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach<th>Acciones</th>
            </tr>
        </thead>
        <tbody>@forelse($rows as $row)<tr>@foreach($definition['columns'] as $field=>$label)<td>@if(in_array($field,['estado','resultado']))<span class="mf-badge {{ in_array($row->$field,['PENDIENTE','OBSERVADA'])?'warning':($row->$field==='RECHAZADA'?'error':'') }}">{{ $row->$field }}</span>@elseif($field==='activo')<span class="mf-badge {{ $row->$field?'':'error' }}">{{ $row->$field?'ACTIVO':'INACTIVO' }}</span>@elseif(is_array($row->$field)){{ json_encode($row->$field,JSON_UNESCAPED_UNICODE) }}@else{{ app(App\Services\DisplayValue::class)->get($row,$field) }}@endif</td>@endforeach<td><a class="table-action" href="{{ $base }}/{{ $row->id }}">Detalle</a><br>@if(App\Services\Modules::editable($module) && auth()->user()->hasPermission($definition['permission']))<a class="table-action" href="{{ $base }}/{{ $row->id }}/edit">Editar</a>@endif @if($module==='liquidaciones' && auth()->user()->hasRole('admin') && $row->estado==='Calculada')<form method="POST" action="/admin/liquidaciones/{{ $row->id }}/pagar">@csrf<button class="mf-button secondary">Registrar pago</button></form>@endif @if($module==='rotaciones' && auth()->user()->hasRole('admin') && $row->estado==='PENDIENTE')<form method="POST" action="/admin/rotaciones/{{ $row->id }}/revisar">@csrf<input name="motivo" placeholder="Motivo de revisión"><button name="decision" value="APROBADA" class="mf-button">Aprobar</button><button name="decision" value="RECHAZADA" class="mf-button secondary">Rechazar</button></form>@endif</td>
            </tr>@empty<tr>
                <td colspan="{{ count($definition['columns'])+1 }}" class="empty">Todavía no hay registros para mostrar.</td>
            </tr>@endforelse</tbody>
    </table>{{ $rows->links() }}
</div>@if($module==='lotes')<p class="muted mt-3">Rendimiento esperado: 11 a 12 quesos por cada 100 L. Yogurt se registra por unidades y no aumenta el stock de queso.</p>@endif @endsection
