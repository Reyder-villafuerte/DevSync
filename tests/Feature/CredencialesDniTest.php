<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredencialesDniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
    }

    public function test_el_personal_entra_a_la_web_con_su_dni(): void
    {
        $this->post('/login', ['dni' => '70000002', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('admin', auth()->user()->role);
    }

    public function test_el_proveedor_usa_el_mismo_dni_en_la_web_y_en_el_movil(): void
    {
        $this->post('/login', ['dni' => '40040013', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertSame('40040013', auth()->user()->dni);

        $this->post('/logout');

        $respuesta = $this->postJson('/api/sync/login', [
            'usuario' => '40040013',
            'password' => 'password',
            'device_id' => 'test-dni',
        ]);

        $respuesta->assertOk();
        $this->assertSame('productor', $respuesta->json('usuario.role'));
    }

    public function test_el_dni_equivocado_no_autentica(): void
    {
        $this->from('/login')
            ->post('/login', ['dni' => '40040013', 'password' => 'clave-mala'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('dni');

        $this->assertGuest();
    }

    public function test_la_cuenta_desactivada_no_inicia_sesion(): void
    {
        User::where('dni', '40010002')->update(['is_active' => false]);

        $this->from('/login')
            ->post('/login', ['dni' => '40010002', 'password' => 'password'])
            ->assertSessionHasErrors('dni');

        $this->assertGuest();
    }

    public function test_los_nueve_roles_tienen_un_acceso_sembrado(): void
    {
        $roles = [
            'jefe_general', 'admin', 'jefe_produccion', 'personal_pago',
            'inspector_calidad', 'personal_venta', 'pagador_campo',
            'acopiador', 'productor',
        ];

        foreach ($roles as $rol) {
            $usuario = User::where('role', $rol)->whereNotNull('dni')->first();
            $this->assertNotNull($usuario, "Falta un usuario sembrado para el rol {$rol}.");

            $this->postJson('/api/sync/login', [
                'usuario' => $usuario->dni,
                'password' => 'password',
            ])->assertOk();
        }
    }

    public function test_hay_52_proveedores_repartidos_de_13_por_zona(): void
    {
        $this->assertSame(52, User::where('role', 'productor')->count());

        $porZona = User::where('role', 'productor')
            ->selectRaw('zone_id, COUNT(*) as total')
            ->groupBy('zone_id')
            ->pluck('total', 'zone_id');

        $this->assertCount(4, $porZona);

        foreach ($porZona as $total) {
            $this->assertSame(13, (int) $total);
        }
    }

    public function test_el_dni_es_unico_entre_usuarios(): void
    {
        $repetidos = User::whereNotNull('dni')
            ->selectRaw('dni, COUNT(*) as total')
            ->groupBy('dni')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->assertSame(0, $repetidos);
    }
}
