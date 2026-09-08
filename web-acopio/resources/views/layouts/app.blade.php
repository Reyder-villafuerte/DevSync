<!DOCTYPE html>
<html lang="es">

<head>
    <script src="/theme.js"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Inicio' }} · MilkFlow</title>@vite(['resources/css/app.css','resources/js/app.js'])<x-pwa-head />
    <link rel="stylesheet" href="/theme.css">
</head>

<body x-data="{menu:false}">
    <header class="mf-topbar"><button class="menu-toggle" @click="menu=!menu" aria-label="Abrir menú">☰</button><a class="brand" href="{{ auth()->user()->dashboard() }}">Milk<span>Flow</span></a><span>HUARI · {{ now()->format('d/m/Y') }}</span>
        <div class="user-chip"><a href="/notificaciones">Avisos ({{ auth()->user()->unreadNotifications()->count() }})</a><span class="avatar">{{ mb_substr(auth()->user()->nombres,0,1) }}</span><span class="user-name">{{ auth()->user()->name }}</span>
            <form method="POST" action="/logout">@csrf<button class="logout">Salir</button></form>
        </div><x-theme-switch />
    </header>
    <div class="app-shell">
        <nav class="sidebar" :class="{'open':menu}"><small>{{ auth()->user()->roles->first()?->name }}</small>@foreach(App\Services\Navigation::menus()[auth()->user()->roles->first()?->slug] ?? [] as $path=>$label)<a href="/{{ auth()->user()->roles->first()->slug }}/{{ $path }}" class="{{ request()->path()===auth()->user()->roles->first()->slug.'/'.$path?'active':'' }}">{{ $label }}</a>@endforeach<div class="side-footer">MilkFlow Web<br>Del campo a la planta.</div>
        </nav>
        <main class="workspace">@if(session('status'))<div class="mf-alert">{{ session('status') }}</div>@endif @if(session('warning'))<div class="mf-alert warning">{{ session('warning') }}</div>@endif @if($errors->any())<div class="mf-alert error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif @yield('content')</main>
    </div>
</body>

</html>
