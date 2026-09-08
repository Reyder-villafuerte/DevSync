@extends('layouts.auth')
@section('title', 'MilkFlow')
@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <label for="login">Correo o usuario</label>
    <input id="login" name="login" value="{{ old('login') }}" autocomplete="username" placeholder="Ingresa tu correo o usuario" required autofocus>
    <x-password-field name="password" label="Contraseña" autocomplete="current-password" />
    <button type="submit" class="mf-button w-full auth-submit">Iniciar sesión</button>
    <a class="forgot" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
</form>
@endsection
@section('footer')
<p class="auth-footer">¿No tienes una cuenta? <a href="{{ route('register') }}">Crear cuenta</a></p>
@endsection
