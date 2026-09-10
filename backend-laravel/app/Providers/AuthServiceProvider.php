<?php

namespace App\Providers;

use App\Enums\RolUsuario;
use App\Models\ControlCalidad;
use App\Models\Liquidacion;
use App\Models\RutaAcopio;
use App\Models\SemanaPago;
use App\Models\SesionProduccion;
use App\Models\SolicitudCambioZona;
use App\Models\Venta;
use App\Policies\ControlCalidadPolicy;
use App\Policies\LiquidacionPolicy;
use App\Policies\RutaAcopioPolicy;
use App\Policies\SemanaPagoPolicy;
use App\Policies\SesionProduccionPolicy;
use App\Policies\SolicitudCambioZonaPolicy;
use App\Policies\VentaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registro explícito de policies (además de la autodiscovery de Laravel).
        Gate::policy(Liquidacion::class, LiquidacionPolicy::class);
        Gate::policy(SemanaPago::class, SemanaPagoPolicy::class);
        Gate::policy(SesionProduccion::class, SesionProduccionPolicy::class);
        Gate::policy(Venta::class, VentaPolicy::class);
        Gate::policy(SolicitudCambioZona::class, SolicitudCambioZonaPolicy::class);
        Gate::policy(RutaAcopio::class, RutaAcopioPolicy::class);
        Gate::policy(ControlCalidad::class, ControlCalidadPolicy::class);

        // Administración puede todo (se evalúa antes que cualquier policy/gate).
        Gate::before(fn ($usuario, $ability) => $usuario->esRol(RolUsuario::ADMINISTRACION) ? true : null);

        // Gate genérico por rol: Gate::allows('rol', RolUsuario::JEFE_PRODUCCION, ...).
        Gate::define('rol', function ($usuario, RolUsuario|string ...$roles) {
            $rolUsuario = $usuario->rol instanceof RolUsuario ? $usuario->rol : RolUsuario::from($usuario->rol);
            $permitidos = array_map(fn ($r) => $r instanceof RolUsuario ? $r : RolUsuario::from($r), $roles);

            return $usuario->activo && in_array($rolUsuario, $permitidos, true);
        });

        // Atajos de capacidad para los paneles Blade.
        Gate::define('panel-jefatura-planta', fn ($u) => $u->esRol(RolUsuario::JEFE_PRODUCCION, RolUsuario::ADMINISTRACION));
        Gate::define('panel-despacho', fn ($u) => $u->esRol(RolUsuario::DESPACHO_VENTAS, RolUsuario::ADMINISTRACION));
        Gate::define('panel-administracion', fn ($u) => $u->esRol(RolUsuario::ADMINISTRACION));
    }
}
