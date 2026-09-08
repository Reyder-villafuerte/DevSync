@extends('layouts.auth')
@section('title', 'Recuperar contraseña')
@section('subtitle', 'Recupera el acceso a tu cuenta de MilkFlow')
@section('content')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <label for="email">Correo electrónico</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
    <button type="submit" class="mf-button w-full auth-submit">Enviar enlace</button>
</form>
@endsection
@section('footer')
<p class="auth-footer"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
@endsection
