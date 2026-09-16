@extends('layouts.app',['title'=>'Mi perfil'])
@section('content')<div class="page-header">
    <h1>Mi perfil</h1>
</div>
<div class="mf-card" style="max-width:650px">
    <h2>{{ auth()->user()->name }}</h2>
    <p>{{ auth()->user()->email }}</p><span class="mf-badge">{{ auth()->user()->roles->first()->name }}</span>
    <form method="POST">@csrf @method('PATCH')<label>Teléfono<input name="telefono" value="{{ old('telefono',auth()->user()->telefono) }}" required></label><button class="mf-button">GUARDAR</button></form>
</div>@endsection
