<?php

namespace Tests\Feature\Panel;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autorización de los paneles: una ruta no permitida devuelve 403 (no una
 * redirección), y un invitado va al login.
 */
class AccesoPanelPorRolTest extends TestCase
{
    use CreaEntidadesPanel;
    use RefreshDatabase;

    public function test_despacho_no_entra_a_administracion(): void
    {
        $this->actingAs($this->usuario(RolUsuario::DESPACHO_VENTAS), 'web')
            ->get('/administracion')
            ->assertForbidden();
    }

    public function test_jefe_de_planta_no_entra_a_despacho(): void
    {
        $this->actingAs($this->usuario(RolUsuario::JEFE_PRODUCCION), 'web')
            ->get('/despacho')
            ->assertForbidden();
    }

    public function test_administracion_entra_a_todos_los_paneles(): void
    {
        $admin = $this->usuario(RolUsuario::ADMINISTRACION);

        $this->actingAs($admin, 'web')->get('/recepcion')->assertOk();
        $this->actingAs($admin, 'web')->get('/despacho')->assertOk();
        $this->actingAs($admin, 'web')->get('/administracion')->assertOk();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/despacho')->assertRedirect(route('panel.login'));
    }
}
