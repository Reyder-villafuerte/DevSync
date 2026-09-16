@extends('layouts.auth')
@section('title', 'Crear cuenta')
@section('subtitle', 'Únete a la red de acopio inteligente')
@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    <fieldset class="auth-name">
        <legend>Nombre completo</legend>
        <div class="form-grid">
            <label>Nombres<input name="nombres" value="{{ old('nombres') }}" autocomplete="given-name" maxlength="100" required></label>
            <label>Apellidos<input name="apellidos" value="{{ old('apellidos') }}" autocomplete="family-name" maxlength="100" required></label>
        </div>
    </fieldset>
    <label for="email">Correo electrónico</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="190" required>
    <x-password-field name="password" label="Contraseña" minlength="10" />
    <small class="auth-help">Mínimo 10 caracteres, mayúscula, minúscula, número y símbolo.</small>
    <x-password-field name="password_confirmation" label="Confirmar contraseña" minlength="10" />
    <fieldset class="auth-details">
        <legend>Datos para verificar tu solicitud</legend>
        <div class="form-grid">
            <label>DNI / Documento<input name="documento" value="{{ old('documento') }}" minlength="8" maxlength="20" required></label>
            <label>Teléfono<input name="telefono" type="tel" value="{{ old('telefono') }}" autocomplete="tel" required></label>
        </div>
    </fieldset>
    <button type="submit" class="mf-button w-full auth-submit">Crear cuenta</button>
    <p class="auth-help auth-review">El administrador revisará tu solicitud y asignará tu rol.</p>
</form>
@endsection
@section('footer')
<p class="auth-footer">¿Ya tienes una cuenta? <a href="{{ route('login') }}">Iniciar sesión</a></p>
@endsection
