@extends('layouts.app',['title'=>'Revisar trabajador'])
@section('content')<div class="page-header">
    <div><span class="eyebrow">SOLICITUD DE REGISTRO</span>
        <h1>{{ $worker->name }}</h1>
    </div><a href="/admin/solicitudes">← Volver</a>
</div>
<div class="content-grid">
    <div class="mf-card">
        <h2>DATOS DEL TRABAJADOR</h2>
        <dl class="detail-grid">@foreach(['nombres'=>'Nombres','apellidos'=>'Apellidos','documento'=>'DNI','email'=>'Correo','telefono'=>'Teléfono','created_at'=>'Fecha de solicitud','status'=>'Estado','approved_at'=>'Fecha de aprobación','rejected_at'=>'Fecha de rechazo','rejection_reason'=>'Motivo de rechazo'] as $field=>$label)<div>
                <dt>{{ $label }}</dt>
                <dd>{{ $worker->$field ?? '—' }}</dd>
            </div>@endforeach</dl>
    </div>
    <div class="mf-card">@if($worker->status==='PENDIENTE')<h2>ASIGNAR ROL</h2>
        <form method="POST" action="/admin/solicitudes/{{ $worker->id }}">@csrf<label>Rol<select name="role_id">
                    <option value="">Selecciona un rol</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
                </select></label><button class="mf-button w-full" name="decision" value="aprobar">APROBAR Y ACTIVAR</button><label>Motivo del rechazo<textarea name="motivo"></textarea></label><button class="mf-button danger w-full" name="decision" value="rechazar">RECHAZAR SOLICITUD</button></form>@elseif(in_array($worker->status,['ACTIVO','INACTIVO']))<h2>Rol y estado</h2>
        <form method="POST" action="/admin/users/{{ $worker->id }}">@csrf @method('PATCH')<label>Rol<select name="role_id">@foreach($roles as $role)<option value="{{ $role->id }}" @selected($worker->roles->contains($role))>{{ $role->name }}</option>@endforeach</select></label><label>Estado<select name="status">@foreach(['ACTIVO','INACTIVO'] as $state)<option @selected($worker->status===$state)>{{ $state }}</option>@endforeach</select></label><button class="mf-button w-full">GUARDAR CAMBIOS</button></form>@else<p>Solicitud rechazada.</p>@endif
    </div>
</div>@endsection
