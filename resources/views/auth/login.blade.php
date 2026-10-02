<!DOCTYPE html>
<html lang="es" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayni Huata - Iniciar sesión</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <script>
        try {
            const m = localStorage.getItem('huata-theme') || 'sistema';
            document.documentElement.dataset.bsTheme = m === 'oscuro' || m === 'sistema' && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .lg-page{min-height:100dvh;display:grid;grid-template-columns:1.15fr 1fr;background:#F5EAD8}
        .lg-hero{position:relative;overflow:hidden;color:#fff;padding:56px 64px;display:flex;flex-direction:column;justify-content:center;
            background:radial-gradient(1200px 600px at 10% 0%,#1E88E5 0%,transparent 60%),linear-gradient(160deg,#1565C0 0%,#0D47A1 60%,#0A3D8F 100%)}
        .lg-wave{position:absolute;left:0;right:0;bottom:-1px;width:100%;height:120px;z-index:0}
        .lg-drop{position:absolute;border-radius:50% 50% 50% 50%/60% 60% 40% 40%;background:#ffffff10;filter:blur(.5px)}
        .lg-logo{width:92px;height:92px;border-radius:26px;background:#F9F4ED;display:grid;place-items:center;box-shadow:0 18px 40px #0D47A166}
        .lg-logo img{width:74px;height:74px;object-fit:contain}
        .lg-eyebrow{font-size:12px;font-weight:800;letter-spacing:.28em;color:#F5EAD8;margin:28px 0 10px}
        .lg-title{font-size:clamp(34px,4.2vw,54px);font-weight:900;line-height:1.05;letter-spacing:-.02em;margin:0}
        .lg-title span{color:#F5EAD8}
        .lg-lead{max-width:560px;margin-top:18px;font-size:16px;line-height:1.65;color:#E3F2FD}
        .lg-flow{position:relative;z-index:1;max-width:640px;margin-top:34px;padding:22px 24px;border-radius:22px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(6px)}
        .lg-flow p{font-size:11px;font-weight:800;letter-spacing:.2em;color:#F5EAD8;margin:0 0 14px}
        .lg-steps{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
        .lg-step{display:inline-flex;align-items:center;gap:8px;padding:9px 14px;border-radius:14px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.22);font-size:13px;font-weight:700}
        .lg-step i{color:#F0FAE1}
        .lg-arrow{color:rgba(255,255,255,.65);font-size:13px}
        .lg-side{display:flex;align-items:center;justify-content:center;padding:40px 28px;
            background:radial-gradient(700px 400px at 100% 0%,#F9F4ED 0%,transparent 60%),#F5EAD8}
        .lg-card{width:100%;max-width:470px;background:#F9F4ED;border:1px solid #DCD3C4;border-radius:30px;padding:44px 40px;box-shadow:0 30px 70px #201E1D1f,0 2px 0 #fff inset}
        .lg-card .eyebrow{font-size:12px;font-weight:800;letter-spacing:.22em;color:#1565C0}
        .lg-card h2{font-size:32px;font-weight:900;color:#201E1D;margin:8px 0 6px}
        .lg-card .sub{font-size:14px;color:#82796A;line-height:1.55}
        .lg-label{display:block;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#645C50;margin-bottom:8px}
        .lg-input{position:relative}
        .lg-input i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#82796A;font-size:15px}
        .lg-input input{width:100%;height:54px;padding:0 16px 0 46px;border-radius:16px;border:1.5px solid #DCD3C4;background:#fff;font-size:15px;color:#201E1D;transition:.15s}
        .lg-input input:focus{outline:none;border-color:#1565C0;box-shadow:0 0 0 4px #1565C01f}
        .lg-btn{width:100%;height:56px;border:0;border-radius:16px;font-size:16px;font-weight:800;color:#fff;cursor:pointer;
            background:linear-gradient(135deg,#E65100,#F57C00);box-shadow:0 12px 26px #E6510055;transition:transform .15s,box-shadow .15s}
        .lg-btn:hover{background:linear-gradient(135deg,#A33C00,#E65100);transform:translateY(-1px);box-shadow:0 16px 30px #E6510066}
        .lg-error{padding:12px 14px;border-radius:14px;background:#FFEBEE;border:1px solid #C6282840;color:#C62828;font-size:13px}
        .lg-demo{margin-top:22px;border:1.5px dashed #DCD3C4;border-radius:16px;background:#EEE7DB}
        .lg-demo summary{list-style:none;cursor:pointer;padding:12px 16px;font-size:13px;font-weight:700;color:#82796A;text-align:center}
        .lg-demo summary::-webkit-details-marker{display:none}
        .lg-demo .grid-demo{display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;padding:4px 18px 16px;font-size:12px;color:#82796A}
        .lg-demo code{font-weight:800;color:#1565C0}
        .lg-foot{margin-top:22px;text-align:center;font-size:12px;color:#82796A}
        [data-bs-theme=dark] .lg-page{background:#0F1E33}[data-bs-theme=dark] .lg-side{background:radial-gradient(700px 400px at 100% 0%,#1E3657 0%,transparent 60%),#0F1E33}
        [data-bs-theme=dark] .lg-card{background:#172B47;border-color:#2A4468}
        [data-bs-theme=dark] .lg-card h2,[data-bs-theme=dark] .lg-label{color:#F5EAD8}
        [data-bs-theme=dark] .lg-input input{background:#0F1E33;border-color:#2A4468;color:#F5EAD8}
        [data-bs-theme=dark] .lg-demo{background:#1E3657;border-color:#2A4468}
        .lg-wave{color:#F5EAD8}
        [data-bs-theme=dark] .lg-wave{color:#0F1E33}
        [data-bs-theme=dark] .lg-card{box-shadow:0 30px 70px #00000055}
        [data-bs-theme=dark] .lg-card .eyebrow,[data-bs-theme=dark] .lg-demo code{color:#64A6EF}
        [data-bs-theme=dark] .lg-card .sub,[data-bs-theme=dark] .lg-foot,[data-bs-theme=dark] .lg-demo summary,[data-bs-theme=dark] .lg-demo .grid-demo{color:#A9B8CC}
        [data-bs-theme=dark] .lg-input i{color:#A9B8CC}
        [data-bs-theme=dark] .lg-input input::placeholder{color:#7f93ad}
        .lg-tema{position:absolute;top:22px;right:24px;z-index:3;display:flex;align-items:center;gap:8px;padding:6px 6px 6px 14px;border-radius:999px;background:#F9F4ED;border:1px solid #DCD3C4;box-shadow:0 6px 18px #201E1D14;font-size:12px;font-weight:700;color:#645C50}
        .lg-tema i{color:#E65100}
        .lg-tema select{border:0;border-radius:999px;background:#EEE7DB;color:#201E1D;font-size:12px;font-weight:700;padding:6px 28px 6px 12px;min-height:0;cursor:pointer}
        [data-bs-theme=dark] .lg-tema{background:#172B47;border-color:#2A4468;color:#C9D4E3}
        [data-bs-theme=dark] .lg-tema select{background:#1E3657;color:#F5EAD8}
        .lg-side{position:relative}
        @media(max-width:991px){.lg-tema{position:fixed;top:12px;right:12px}.lg-page{grid-template-columns:1fr}.lg-hero{padding:40px 24px 120px}.lg-flow{display:none}.lg-side{padding:0 16px 40px;margin-top:-70px;background:transparent;position:relative;z-index:2}.lg-card{padding:32px 24px}}
    </style>
</head>

<body class="antialiased">
    <div class="lg-page">

        {{-- ===== Lado institucional ===== --}}
        <section class="lg-hero">
            <span class="lg-drop" style="width:260px;height:300px;right:-60px;top:-40px"></span>
            <span class="lg-drop" style="width:140px;height:160px;right:22%;bottom:22%"></span>
            <svg class="lg-wave" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0,60 C150,110 300,10 450,50 C600,90 750,20 900,55 C1020,82 1110,60 1200,40 L1200,120 L0,120 Z" fill="currentColor" opacity=".35"/>
                <path d="M0,85 C180,120 320,50 520,80 C700,108 860,60 1020,82 C1100,92 1160,88 1200,80 L1200,120 L0,120 Z" fill="currentColor"/>
            </svg>

            <div style="position:relative;z-index:1">
                <div class="lg-logo"><img src="{{ asset('brand/huata-simbolo-vaca.png') }}" alt="Símbolo de Ecolácteos Huata"></div>
                <p class="lg-eyebrow">ECOLÁCTEOS HUATA · PUNO</p>
                <h1 class="lg-title">Ayni <span>Huata</span></h1>
                <p class="lg-lead">
                    Plataforma de la asociación de productores para el acopio de leche en ruta, la verificación en planta,
                    la producción de quesos y derivados, las ventas y el pago justo a cada productor.
                </p>

                <div class="lg-flow">
                    <p>EL RECORRIDO DE LA LECHE</p>
                    <div class="lg-steps">
                        <span class="lg-step"><i class="fa-solid fa-truck-droplet"></i> 1. Acopio 4:30 AM</span>
                        <span class="lg-arrow">→</span>
                        <span class="lg-step"><i class="fa-solid fa-gauge-high"></i> 2. Planta</span>
                        <span class="lg-arrow">→</span>
                        <span class="lg-step"><i class="fa-solid fa-cheese"></i> 3. Producción</span>
                        <span class="lg-arrow">→</span>
                        <span class="lg-step"><i class="fa-solid fa-hand-holding-dollar"></i> 4. Venta y pago</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Formulario ===== --}}
        <section class="lg-side">
            <label class="lg-tema" title="Cambiar apariencia">
                <i class="fa-solid fa-circle-half-stroke"></i> Apariencia
                <select data-huata-theme aria-label="Apariencia">
                    <option value="sistema">Sistema</option>
                    <option value="claro">Claro</option>
                    <option value="oscuro">Oscuro</option>
                </select>
            </label>
            <div class="lg-card">
                <span class="eyebrow">PORTAL DE ACCESO</span>
                <h2>Iniciar sesión</h2>
                <p class="sub">Ingresa con tu DNI y contraseña. Son los mismos que usas en la app móvil.</p>

                @if($errors->any())
                    <div class="lg-error mt-4">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="dni" class="lg-label">DNI</label>
                        <div class="lg-input">
                            <i class="fa-regular fa-id-card"></i>
                            <input type="text" id="dni" name="dni" value="{{ old('dni') }}" required autofocus
                                autocomplete="username" inputmode="numeric" maxlength="20" placeholder="Tu número de DNI">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="lg-label">Contraseña</label>
                        <div class="lg-input">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="password" name="password" required
                                autocomplete="current-password" placeholder="Tu contraseña">
                        </div>
                    </div>

                    <button type="submit" class="lg-btn">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Ingresar al sistema
                    </button>
                </form>

                @if(app()->environment('local'))
                <details class="lg-demo">
                    <summary><i class="fa-solid fa-flask me-1"></i> Accesos de demostración (clave: <code>password</code>) <i class="fa-solid fa-chevron-down ms-1"></i></summary>
                    <div class="grid-demo">
                        <div>Jefe general: <code>70000001</code></div>
                        <div>Admin: <code>70000002</code></div>
                        <div>Jefe planta: <code>70000003</code></div>
                        <div>Pagos: <code>70000004</code></div>
                        <div>Calidad: <code>70000005</code></div>
                        <div>Ventas: <code>70000006</code></div>
                        <div>Pagador campo: <code>70000007</code></div>
                        <div>Acopiador: <code>71110001</code></div>
                        <div>Productor Z1: <code>40010001</code></div>
                        <div>Productor Z4: <code>40040013</code></div>
                    </div>
                </details>
                @endif

                <p class="lg-foot">Ecolácteos Huata · Productivo y sostenible</p>
            </div>
        </section>
    </div>
</body>

</html>
