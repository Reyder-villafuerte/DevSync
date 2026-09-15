<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MilkFlow Huata - @yield('title', 'Panel del día')</title>
    <script>try{const m=localStorage.getItem('huata-theme')||'sistema';document.documentElement.dataset.bsTheme=m==='oscuro'||m==='sistema'&&matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'}catch(e){}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-spark-surface text-slate-800 antialiased flex">

    @auth
    <!-- SIDEBAR ESTILO SPARK ADMIN (OSCURO) -->
    <aside id="huataMenu" tabindex="-1" aria-label="Menú principal" class="huata-sidebar offcanvas-lg offcanvas-start w-64 bg-spark-dark text-slate-300 flex-shrink-0 flex flex-col justify-between p-4 no-print border-r border-emerald-950/40">
        <div>
            <div class="huata-brand"><img src="{{ asset('brand/huata-simbolo-vaca.png') }}" alt="Ecolácteos Huata" width="86" height="86"><span>MilkFlow<br><small>Hecho para nuestra comunidad</small></span><button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#huataMenu" aria-label="Cerrar menú"></button></div>
            <!-- Navegación Categorizada -->
            <nav class="space-y-6 text-xs font-semibold">
                @if(Auth::user()->role === 'productor')
                <!-- NAVEGACIÓN EXCLUSIVA: PORTAL PROVEEDOR / PRODUCTOR -->
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold px-3 block mb-2">MENU</span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('productor.acopio') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ (request()->routeIs('productor.acopio') || request()->routeIs('dashboard')) ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-bucket text-sm {{ (request()->routeIs('productor.acopio') || request()->routeIs('dashboard')) ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Acopio</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('productor.zonas') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('productor.zonas*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-arrows-split-up-and-left text-sm {{ request()->routeIs('productor.zonas*') ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Cambio de Zona</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('productor.descuentos') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('productor.descuentos*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-receipt text-sm {{ request()->routeIs('productor.descuentos*') ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Descuentos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('productor.pagos') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('productor.pagos*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-money-bill-transfer text-sm {{ request()->routeIs('productor.pagos*') ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Historial de Pagos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('productor.calidad') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('productor.calidad*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-vial-circle-check text-sm {{ request()->routeIs('productor.calidad*') ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Calidad</span>
                            </a>
                        </li>
                    </ul>
                </div>
                @elseif(Auth::user()->role === 'pagador_campo')
                <!-- NAVEGACIÓN EXCLUSIVA: PAGADOR DE CAMPO (SOLO PAGOS EN RUTA) -->
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold px-3 block mb-2">PAGOS EN RUTA</span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('pagos.ruta.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ (request()->routeIs('pagos.ruta.index') || request()->routeIs('pagos.ruta.receipt')) ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-envelope-open-text text-sm {{ (request()->routeIs('pagos.ruta.index') || request()->routeIs('pagos.ruta.receipt')) ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Planilla de Sobres</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('pagos.ruta.history') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('pagos.ruta.history') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-clock-rotate-left text-sm {{ request()->routeIs('pagos.ruta.history') ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Historial de Pagos</span>
                            </a>
                        </li>
                    </ul>
                </div>
                @else
                <!-- SECCIÓN: MENU GENERAL -->
                @if(!in_array(Auth::user()->role, ['acopiador', 'jefe_produccion']))
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold px-3 block mb-2">MENU</span>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('dashboard') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-shapes text-sm {{ request()->routeIs('dashboard') ? 'text-spark-dark' : 'text-slate-400' }}"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>
                @endif

                <!-- SECCIÓN: OPERACIONES HUATA -->
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold px-3 block mb-2">OPERACIONES HUATA</span>
                    <ul class="space-y-1">
                        @if(in_array(Auth::user()->role, ['acopiador', 'admin', 'jefe_general']))
                        <li>
                            <a href="{{ route('acopio.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('acopio.index') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-truck-fast text-sm"></i>
                                <span>Acopio 4:30 AM</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('acopio.historial') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('acopio.historial') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                                <span>Historial y Reportes</span>
                            </a>
                        </li>
                        @endif

                        @if(in_array(Auth::user()->role, ['admin', 'jefe_general']))
                        <li>
                            <a href="{{ route('zonas.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('zonas.index') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-map-location-dot text-sm"></i>
                                <span>Zonas (1 a 4)</span>
                            </a>
                        </li>
                        @endif

                        @if(in_array(Auth::user()->role, ['admin', 'jefe_general']))
                        <li>
                            <a href="{{ route('zonas.solicitudes') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('zonas.solicitudes*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-arrows-split-up-and-left text-sm"></i>
                                    <span>Solicitudes de Zona</span>
                                </div>
                                @php
                                    $pendingZoneCount = \App\Models\ZoneChangeRequest::where('status', 'pendiente')->count();
                                @endphp
                                @if($pendingZoneCount > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ request()->routeIs('zonas.solicitudes*') ? 'bg-spark-dark text-spark-lime' : 'bg-spark-lime text-spark-dark' }}">
                                        {{ $pendingZoneCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- SECCIÓN: PLANTA Y CALIDAD -->
                @if(in_array(Auth::user()->role, ['jefe_produccion', 'inspector_calidad', 'admin', 'jefe_general']))
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold px-3 block mb-2">PLANTA Y CALIDAD</span>
                    <ul class="space-y-1">
                        @if(in_array(Auth::user()->role, ['jefe_produccion', 'jefe_general']))
                        <li>
                            <a href="{{ route('planta.verificacion') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('planta.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-water text-sm"></i>
                                    <span>Caudalímetro</span>
                                </div>
                                @php
                                    $pendingDischargeCount = \App\Models\CollectionRoute::where('date', date('Y-m-d'))->where('status', 'descargada_planta')->count();
                                @endphp
                                @if($pendingDischargeCount > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ request()->routeIs('planta.*') ? 'bg-spark-dark text-spark-lime' : 'bg-[#bef264] text-[#0f1713]' }}" title="{{ $pendingDischargeCount }} ruta(s) descargada(s) esperando caudalímetro">
                                        {{ $pendingDischargeCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('produccion.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('produccion.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-cheese text-sm"></i>
                                <span>Quesería (-10L)</span>
                            </a>
                        </li>
                        @endif

                        @if(in_array(Auth::user()->role, ['inspector_calidad', 'admin', 'jefe_general']))
                        <li>
                            <a href="{{ route('calidad.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('calidad.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-microscope text-sm"></i>
                                <span>Lactoscan & Citas</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif

                <!-- SECCIÓN: DESPACHO Y PAGOS -->
                @if(in_array(Auth::user()->role, ['personal_venta', 'personal_pago', 'pagador_campo', 'admin', 'jefe_general']))
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold px-3 block mb-2">
                        {{ in_array(Auth::user()->role, ['admin', 'jefe_general']) ? 'FINANZAS Y DESPACHO' : 'DESPACHO Y PAGOS' }}
                    </span>
                    <ul class="space-y-1">
                        {{-- Flujo de Caja: Exclusivo para Supervisión Financiera (Admin y Jefe General) --}}
                        @if(in_array(Auth::user()->role, ['admin', 'jefe_general']))
                        <li>
                            <a href="{{ route('admin.finanzas.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.finanzas.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-scale-balanced text-sm"></i>
                                <span>Flujo de Caja</span>
                            </a>
                        </li>
                        @endif

                        {{-- Ventas (POS Mostrador): Personal de Venta (Admin no vende) --}}
                        @if(in_array(Auth::user()->role, ['personal_venta', 'jefe_general']))
                        <li>
                            <a href="{{ route('ventas.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('ventas.index') || request()->routeIs('ventas.create') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-cart-shopping text-sm"></i>
                                <span>Ventas</span>
                            </a>
                        </li>
                        @endif

                        {{-- Recibos: Para Personal de Venta, Admin y Jefe General --}}
                        @if(in_array(Auth::user()->role, ['personal_venta', 'admin', 'jefe_general']))
                        <li>
                            <a href="{{ route('ventas.receipts') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('ventas.receipts*') || request()->routeIs('ventas.receipt') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-receipt text-sm"></i>
                                <span>Recibos</span>
                            </a>
                        </li>
                        @endif

                        {{-- Autorización de Pagos Semanales: Admin, Personal Pago y Jefe General --}}
                        @if(in_array(Auth::user()->role, ['personal_pago', 'admin', 'jefe_general']))
                        <li>
                            <a href="{{ route('admin.pagos.autorizacion') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.pagos.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-money-check-dollar text-sm"></i>
                                    <span>Autorizar Pagos</span>
                                </div>
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black bg-[#bef264] text-[#0f1713]">
                                    Semana
                                </span>
                            </a>
                        </li>
                        @endif

                        {{-- Sobres en Ruta (Viernes): Exclusivo Pagador de Campo y Personal de Pago (Admin no va a pagar) --}}
                        @if(in_array(Auth::user()->role, ['pagador_campo', 'personal_pago', 'jefe_general']))
                        <li>
                            <a href="{{ route('pagos.ruta.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('pagos.ruta.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-envelope-open-text text-sm"></i>
                                <span>Sobres en Ruta</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif

                <!-- SECCIÓN: GOBERNANZA Y SISTEMA -->
                @if(in_array(Auth::user()->role, ['admin', 'jefe_general']))
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold px-3 block mb-2">SISTEMA</span>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('admin.precios.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.precios.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-tags text-sm"></i>
                                <span>Tarifas & Precios</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('anuncios.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('anuncios.*') ? 'bg-spark-lime text-spark-dark font-bold shadow-sm' : 'hover:bg-white/5 hover:text-white text-slate-400' }}">
                                <i class="fa-solid fa-bullhorn text-sm"></i>
                                <span>Avisos Login</span>
                            </a>
                        </li>
                    </ul>
                </div>
                @endif
                @endif
            </nav>
        </div>

        <!-- Usuario actual inferior estilo Spark -->
        <div class="pt-4 border-t border-white/10">
            <div class="flex items-center justify-between p-2 rounded-xl bg-white/5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-white uppercase border border-white/20">
                        {{ substr(Auth::user()->name, 0, 2) }}
                    </div>
                    <div class="overflow-hidden">
                        <h5 class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</h5>
                        <p class="text-[10px] text-slate-400 uppercase tracking-tight">{{ str_replace('_', ' ', Auth::user()->role) }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Cerrar Sesión" class="text-slate-400 hover:text-red-400 text-xs p-1.5 transition">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    @endauth

    <!-- CONTENEDOR DERECHO PRINCIPAL -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto">
        
        @auth
        <!-- NAVBAR SUPERIOR ESTILO SPARK -->
        <header class="huata-header no-print">
            <button class="btn huata-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#huataMenu" aria-controls="huataMenu"><i class="fa-solid fa-bars" aria-hidden="true"></i> Menú</button>
            <img class="huata-wordmark" src="{{ asset('brand/huata-letras.png') }}" alt="Ecolácteos Huata, productivo y sostenible">
            <div class="huata-header-actions"><span class="hidden md:inline">{{ Auth::user()->name }}</span>@include('partials.theme-selector')</div>
        </header>
        @endauth

        <!-- ÁREA DE CONTENIDO -->
        <main class="flex-1 p-6 md:p-8 max-w-7xl w-full mx-auto">
            
            <!-- Mensajes Flash / Alertas -->
            @if(session('success'))
                <div class="p-4 mb-6 text-xs text-emerald-900 rounded-2xl bg-emerald-50 border border-emerald-200/60 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 mb-6 text-xs text-rose-900 rounded-2xl bg-rose-50 border border-rose-200/60 shadow-sm">
                    <div class="font-bold flex items-center gap-2 mb-1 text-sm">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Atención:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-700">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Comunicados de Login Activos -->
            @if(session('login_announcements') && count(session('login_announcements')) > 0)
                @foreach(session('login_announcements') as $anuncio)
                    <div class="p-5 mb-6 rounded-3xl bg-spark-cardDark text-white shadow-md relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="z-10">
                            <span class="text-[10px] bg-spark-lime text-spark-dark font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                                COMUNICADO OFICIAL
                            </span>
                            <h4 class="text-base font-bold text-white mt-1">{{ data_get($anuncio, 'title') }}</h4>
                            <p class="text-xs text-slate-300 mt-1 max-w-2xl">{{ data_get($anuncio, 'message') }}</p>
                        </div>
                        <span class="text-xs text-slate-400 font-mono z-10 whitespace-nowrap">
                            Vigente: {{ data_get($anuncio, 'start_date') }} → {{ data_get($anuncio, 'end_date') }}
                        </span>
                        <i class="fa-solid fa-asterisk text-7xl text-white/5 absolute -right-4 -bottom-4"></i>
                    </div>
                @endforeach
            @endif

            @yield('content')
        </main>

        <footer class="p-6 text-center text-xs text-slate-400 no-print border-t border-slate-200/50 mt-auto">
            Ecolácteos Huata · Productivo y sostenible
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
