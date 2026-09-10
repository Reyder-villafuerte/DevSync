@extends('layouts.panel')
@section('titulo', 'Acceso')
@section('contenido')
    <div class="tarjeta" style="max-width:380px;margin:4rem auto;">
        <h1>Panel MilkFlow</h1>
        <form method="POST" action="{{ url('login') }}">
            @csrf
            <div class="campo">
                <label for="dni">DNI</label>
                <input id="dni" name="dni" value="{{ old('dni') }}" required inputmode="numeric" pattern="[0-9]{8}" autofocus>
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input id="password" type="password" name="password" required>
            </div>
            <div class="campo">
                <label style="font-weight:normal"><input type="checkbox" name="recordar" value="1" style="width:auto"> Recordarme</label>
            </div>
            @error('dni') <span class="error-campo">{{ $message }}</span> @enderror
            <button type="submit" class="principal" style="margin-top:.5rem">Ingresar</button>
        </form>
    </div>
@endsection
