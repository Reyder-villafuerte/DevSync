<?php

namespace Tests\Feature\Panel;

use App\Enums\RolUsuario;
use App\Livewire\Panel\Admin\AsistenciaAsamblea;
use App\Livewire\Panel\Admin\Avisos;
use App\Livewire\Panel\Admin\LiquidacionViernes;
use App\Livewire\Panel\Admin\SolicitudesCambioRuta;
use App\Livewire\Panel\Admin\Tarifas;
use App\Livewire\Panel\Admin\Usuarios;
use App\Livewire\Panel\Despacho\StockDisponible;
use App\Livewire\Panel\Recepcion\SemaforoRendimiento;
use App\Livewire\Panel\Recepcion\SesionesProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Humo: cada componente Livewire de los paneles monta y renderiza sin error
 * (las pestañas de administración sólo se cargan por wire:click, así que no
 * quedan cubiertas por las pruebas de acceso de ruta).
 */
class ComponentesRenderizanTest extends TestCase
{
    use CreaEntidadesPanel;
    use RefreshDatabase;

    public function test_componentes_de_administracion(): void
    {
        $admin = $this->usuario(RolUsuario::ADMINISTRACION);

        foreach ([Tarifas::class, LiquidacionViernes::class, AsistenciaAsamblea::class, Avisos::class, Usuarios::class, SolicitudesCambioRuta::class] as $componente) {
            Livewire::actingAs($admin)->test($componente)->assertOk();
        }
    }

    public function test_componentes_de_recepcion_y_despacho(): void
    {
        $jefe = $this->usuario(RolUsuario::JEFE_PRODUCCION);
        Livewire::actingAs($jefe)->test(SesionesProduccion::class)->assertOk();
        Livewire::actingAs($jefe)->test(SemaforoRendimiento::class)->assertOk();

        $vendedor = $this->usuario(RolUsuario::DESPACHO_VENTAS);
        Livewire::actingAs($vendedor)->test(StockDisponible::class)->assertOk();
    }
}
