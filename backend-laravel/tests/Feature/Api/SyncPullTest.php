<?php

namespace Tests\Feature\Api;

use App\Enums\RolUsuario;
use App\Models\Acopiador;
use App\Models\Productor;
use App\Models\Ruta;
use App\Models\Zona;
use Illuminate\Support\Carbon;

class SyncPullTest extends ApiTestCase
{
    private function territorio(): array
    {
        $r1 = Ruta::create(['nombre' => 'Ruta Norte', 'codigo' => '01']);
        $r2 = Ruta::create(['nombre' => 'Ruta Sur', 'codigo' => '02']);
        $z1 = Zona::create(['nombre' => 'Cabana', 'codigo' => 'cabana', 'ruta_id' => $r1->id]);
        $z2 = Zona::create(['nombre' => 'Acora', 'codigo' => 'acora', 'ruta_id' => $r2->id]);

        return [$r1, $r2, $z1, $z2];
    }

    private function productor(Zona $zona, string $dni): Productor
    {
        return Productor::create([
            'codigo_padron' => 'P'.$dni, 'nombres' => 'N', 'apellidos' => 'A', 'dni' => $dni,
            'zona_id' => $zona->id, 'estado' => 'activo', 'fecha_ingreso' => '2024-01-01',
        ]);
    }

    public function test_el_acopiador_solo_recibe_el_padron_de_su_ruta(): void
    {
        [$r1, $r2, $z1, $z2] = $this->territorio();
        $mio = $this->productor($z1, '70000001');
        $ajeno = $this->productor($z2, '70000002');

        [$usuario, $disp, $headers] = $this->autenticar(RolUsuario::ACOPIADOR);
        Acopiador::create(['usuario_id' => $usuario->id, 'ruta_id' => $r1->id, 'vigente_desde' => '2024-01-01']);

        $resp = $this->getJson('/api/sync/pull', $headers)->assertOk();

        $ids = collect($resp->json('cambios.productores'))->pluck('id');
        $this->assertTrue($ids->contains($mio->id));
        $this->assertFalse($ids->contains($ajeno->id));

        // No ve entidades de planta.
        $this->assertArrayNotHasKey('preciosVenta', $resp->json('cambios'));
        $this->assertArrayNotHasKey('movimientosStock', $resp->json('cambios'));
        $this->assertSame('ruta:01', $resp->json('ambito'));
    }

    public function test_pull_incremental_solo_devuelve_cambios_posteriores_al_cursor(): void
    {
        [$r1,, $z1] = $this->territorio();
        [$usuario,, $headers] = $this->autenticar(RolUsuario::ADMINISTRACION);

        $viejo = $this->productor($z1, '70000010');
        Carbon::setTestNow(now()->addMinutes(5));
        $corte = now()->toISOString();
        Carbon::setTestNow(now()->addMinutes(5));
        $nuevo = $this->productor($z1, '70000011');
        Carbon::setTestNow();

        $resp = $this->getJson('/api/sync/pull?desde='.urlencode($corte), $headers)->assertOk();
        $ids = collect($resp->json('cambios.productores'))->pluck('id');

        $this->assertTrue($ids->contains($nuevo->id));
        $this->assertFalse($ids->contains($viejo->id));
    }

    public function test_incluye_registros_con_borrado_logico(): void
    {
        [,, $z1] = $this->territorio();
        [$usuario,, $headers] = $this->autenticar(RolUsuario::ADMINISTRACION);
        $p = $this->productor($z1, '70000020');
        $p->borrarLogico();

        $resp = $this->getJson('/api/sync/pull', $headers)->assertOk();
        $fila = collect($resp->json('cambios.productores'))->firstWhere('id', $p->id);

        $this->assertNotNull($fila);
        $this->assertTrue($fila['deleted']);
    }
}
