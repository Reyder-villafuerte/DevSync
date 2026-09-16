@extends('layouts.app',['title'=>'Solicitudes y usuarios'])
@section('content')<div class="page-header">
    <div><span class="eyebrow">ADMINISTRACIÓN</span>
        <h1>{{ request()->is('admin/solicitudes')?'Solicitudes de registro':'Usuarios' }}</h1>
        <p class="muted">Solicitudes pendientes: {{ App\Models\User::where('status','PENDIENTE')->count() }}</p>
    </div>
</div>
<div class="mf-card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Correo</th>
                <th>Fecha de registro</th>
                <th>Estado / Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>@forelse($users as $worker)<tr>
                <td>{{ $worker->name }}</td>
                <td>{{ $worker->documento }}</td>
                <td>{{ $worker->email }}</td>
                <td>{{ $worker->created_at->format('d/m/Y H:i') }}</td>
                <td><span class="mf-badge {{ $worker->status==='PENDIENTE'?'warning':'' }}">{{ $worker->status }}</span><br>{{ $worker->roles->first()?->name }}</td>
                <td>@if($worker->id!==auth()->id())<a class="table-action" href="/admin/solicitudes/{{ $worker->id }}">REVISAR</a>@endif</td>
            </tr>@empty<tr>
                <td colspan="6" class="empty">No hay solicitudes en este estado.</td>
            </tr>@endforelse</tbody>
    </table>{{ $users->links() }}
</div>@endsection
