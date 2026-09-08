@extends('layouts.auth')
@section('title', 'Nueva contraseña')
@section('content')
<form method="POST" action="/reset-password">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label for="email">Correo electrónico</label>
    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', request('email')) }}" required>
    <x-password-field name="password" label="Contraseña" minlength="10" />
    <small class="auth-help">Mínimo 10 caracteres, mayúscula, minúscula, número y símbolo.</small>
    <x-password-field name="password_confirmation" label="Confirmar contraseña" minlength="10" />
    <button class="mf-button w-full auth-submit">Guardar contraseña</button>
</form>
@endsection
