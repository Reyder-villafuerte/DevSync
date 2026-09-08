<!DOCTYPE html>
<html lang="es">
<head><script src="/theme.js"></script>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">

<title>@yield('title', 'Acceso') · MilkFlow</title>
@vite(['resources/css/app.css','resources/js/app.js'])
<x-pwa-head /><link rel="stylesheet" href="/theme.css"></head>
<body class="auth-page">
<header class="mf-topbar"><a href="{{ route('login') }}" class="brand">Milk<span>Flow</span></a><x-theme-switch /></header>
<main class="auth-shell">
<div class="auth-heading">

<h1>@yield('title', 'MilkFlow')</h1>
<p>@yield('subtitle', 'Gestión inteligente del acopio de leche')</p>
</div>
<section class="auth-form mf-card" aria-label="Formulario de acceso">
@if(session('status'))<div class="mf-alert" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="mf-alert error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@yield('content')
</section>
@yield('footer')
</main></body></html>
