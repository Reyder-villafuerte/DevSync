@extends('layouts.app',['title'=>'Notificaciones'])
@section('content')<div class="page-header">
    <h1>Notificaciones</h1>
</div>@forelse($notifications as $notice)<div class="mf-card">
    <h2>{{ $notice->data['titulo'] }}</h2>
    <p>{{ $notice->data['mensaje'] }}</p><small class="muted">{{ $notice->created_at->format('d/m/Y H:i') }}</small>@unless($notice->read_at)<form method="POST" action="/notificaciones/{{ $notice->id }}/leer" class="mt-3">@csrf<button class="mf-button secondary">Marcar como leído</button></form>@endunless
</div>@empty<div class="mf-card empty">No tienes notificaciones.</div>@endforelse {{ $notifications->links() }}@endsection
