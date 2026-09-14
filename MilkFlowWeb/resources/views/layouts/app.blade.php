<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MilkFlow Huata - @yield('title', 'Spark Dashboard')</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        spark: {
                            dark: '#0f1713',       // Sidebar fondo negro verdoso profundo
                            darker: '#090e0b',
                            cardDark: '#17271f',   // Tarjeta destacada verde bosque
                            surface: '#f4f6f5',    // Fondo general gris muy sutil
                            border: '#e5e7eb',
                            lime: '#bef264',       // Verde lima vibrante botón / insignia
                            limeDark: '#a3e635',
                            limeText: '#4d7c0f',
                            muted: '#94a3b8',
                        }
                    },
                    borderRadius: {
                        '2xl': '1.25rem',
                        '3xl': '1.75rem',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f6f5;
        }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
    </style>
</head>
<body class="h-full bg-spark-surface text-slate-800 antialiased flex">

    @auth
    <!-- SIDEBAR ESTILO SPARK ADMIN (OSCURO) -->
    <aside class="w-64 bg-spark-dark text-slate-300 flex-shrink-0 flex flex-col justify-between p-4 no-print border-r border-emerald-950/40">
        <div>
            <!-- Logo Spark / MilkFlow -->
            <div class="flex items-center gap-3 px-3 py-4 mb-4">
                <div class="w-8 h-8 rounded-lg bg-spark-lime flex items-center justify-center text-spark-dark shadow-sm">
                    <i class="fa-solid fa-asterisk text-lg font-black"></i>
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white flex items-center gap-1.5">
                        MilkFlow <span class="text-[10px] bg-spark-lime/20 text-spark-lime px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Huata</span>
                    </span>
                </div>
            </div>

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
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-20 px-8 py-4 border-b border-slate-200/70 flex justify-between items-center no-print">
            <div class="flex items-center gap-3 flex-1 max-w-xl">
                <!-- Botón de acción rápida '+ Crear' -->
                @if(in_array(Auth::user()->role, ['personal_venta', 'admin', 'jefe_general']))
                <a href="{{ route('ventas.create') }}" class="bg-spark-dark hover:bg-black text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition shadow-sm">
                    <i class="fa-solid fa-plus text-spark-lime text-xs"></i> <span>+ Venta</span>
                </a>
                @endif

                <!-- Buscador tipo Pill redondeado de Spark -->
                <div class="relative w-full max-w-md">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" placeholder="Buscar proveedores, zonas, lotes o recibos en Spark..." 
                        class="w-full bg-slate-100/80 focus:bg-white text-xs pl-9 pr-4 py-2 rounded-full border border-slate-200 focus:outline-none focus:ring-2 focus:ring-spark-lime focus:border-transparent transition placeholder:text-slate-400">
                </div>
            </div>

            <!-- Controles a la derecha (Alertas, Rango, Usuario) -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 bg-white px-3 py-1.5 rounded-full border border-slate-200 text-xs font-medium text-slate-600 shadow-sm">
                    <i class="fa-regular fa-calendar text-slate-400"></i>
                    <span>{{ date('F j, Y') }}</span>
                </div>

                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 cursor-pointer relative transition">
                    <i class="fa-regular fa-bell text-xs"></i>
                    @if(session('login_announcements') && count(session('login_announcements')) > 0)
                    <span class="w-2 h-2 rounded-full bg-spark-lime ring-2 ring-white absolute top-2 right-2"></span>
                    @endif
                </div>

                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-spark-dark text-spark-lime font-bold text-xs flex items-center justify-center">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <span class="text-xs font-bold text-slate-700 hidden md:inline-block">{{ Auth::user()->name }}</span>
                </div>
            </div>
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
                            <h4 class="text-base font-bold text-white mt-1">{{ $anuncio->title }}</h4>
                            <p class="text-xs text-slate-300 mt-1 max-w-2xl">{{ $anuncio->message }}</p>
                        </div>
                        <span class="text-xs text-slate-400 font-mono z-10 whitespace-nowrap">
                            Vigente: {{ $anuncio->start_date }} → {{ $anuncio->end_date }}
                        </span>
                        <i class="fa-solid fa-asterisk text-7xl text-white/5 absolute -right-4 -bottom-4"></i>
                    </div>
                @endforeach
            @endif

            @yield('content')
        </main>

        <footer class="p-6 text-center text-xs text-slate-400 no-print border-t border-slate-200/50 mt-auto">
            MilkFlow Huata &copy; 2026 — Diseñado con lenguaje visual Spark Admin para el Distrito de Huata
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
