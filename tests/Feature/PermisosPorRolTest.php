<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un productor no puede ejecutar acciones de otros roles escribiendo la URL:
 * el menú las oculta y la ruta las rechaza con 403.
 */
class PermisosPorRolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
    }

    public function test_un_productor_no_puede_ejecutar_acciones_de_otros_roles(): void
    {
        $productor = User::where('role', 'productor')->firstOrFail();
        $otro = User::where('role', 'productor')->where('id', '!=', $productor->id)->firstOrFail();

        $this->actingAs($productor)->post("/productor/liquidar/{$otro->id}")->assertForbidden();
        $this->actingAs($productor)->post("/admin/pagos/autorizar/{$otro->id}")->assertForbidden();
        $this->actingAs($productor)->post('/admin/pagos/autorizar-todos')->assertForbidden();
        $this->actingAs($productor)->post("/pagos/ruta/pagar/{$otro->id}")->assertForbidden();
        $this->actingAs($productor)->post('/admin/finanzas/egreso')->assertForbidden();
        $this->actingAs($productor)->post('/anuncios')->assertForbidden();
        $this->actingAs($productor)->post('/ventas')->assertForbidden();
        $this->actingAs($productor)->post('/ventas/cierre-caja')->assertForbidden();
        $this->actingAs($productor)->post('/calidad/analisis')->assertForbidden();
        $this->actingAs($productor)->post('/planta/verificar/1')->assertForbidden();
        $this->actingAs($productor)->post('/zonas/solicitud/1/revisar')->assertForbidden();
    }

    public function test_un_acopiador_no_puede_ver_finanzas_ni_pagos(): void
    {
        $acopiador = User::where('role', 'acopiador')->firstOrFail();

        $this->actingAs($acopiador)->get('/admin/finanzas')->assertForbidden();
        $this->actingAs($acopiador)->get('/admin/pagos/autorizacion')->assertForbidden();
        $this->actingAs($acopiador)->get('/anuncios')->assertForbidden();
    }

    public function test_la_api_movil_antigua_ya_no_existe(): void
    {
        $this->postJson('/api/mobile/login', ['email' => 'x@x.com', 'password' => 'x'])->assertNotFound();
    }

    public function test_el_login_limita_los_intentos(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['dni' => '00000000', 'password' => 'mala']);
        }

        $this->post('/login', ['dni' => '00000000', 'password' => 'mala'])->assertStatus(429);
    }
}
