<?php

namespace Tests\Feature\Api;

use App\Enums\RolUsuario;
use App\Models\Productor;
use App\Models\Ruta;
use App\Models\Zona;
use Illuminate\Support\Str;

class InspeccionTest extends ApiTestCase
{
    private function productor(): Productor
    {
        $ruta = Ruta::create(['nombre' => 'R', 'codigo' => '01']);
        $zona = Zona::create(['nombre' => 'Z', 'codigo' => 'z', 'ruta_id' => $ruta->id]);

        return Productor::create([
            'codigo_padron' => 'P1', 'nombres' => 'N', 'apellidos' => 'A', 'dni' => '70000001',
            'zona_id' => $zona->id, 'estado' => 'activo', 'fecha_ingreso' => '2024-01-01',
        ]);
    }

    public function test_registra_inspeccion_y_devuelve_dictamen_evaluado(): void
    {
        $productor = $this->productor();
        [,, $headers] = $this->autenticar(RolUsuario::SUPERVISOR_CALIDAD);
        $id = (string) Str::uuid();

        $resp = $this->postJson('/api/inspecciones', [
            'id' => $id,
            'productorId' => $productor->id,
            'aguaAnadidaPorcentaje' => 3.0,
        ], $headers)->assertOk();

        $resp->assertJsonPath('control.dictamen', 'advertencia_agua')
            ->assertJsonPath('control.id', $id)
            ->assertJsonPath('sancion.tipo', 'advertencia_descuento');

        // Idempotente: reenviar no crea otra inspección ni otra sanción.
        $this->postJson('/api/inspecciones', ['id' => $id, 'productorId' => $productor->id, 'aguaAnadidaPorcentaje' => 3.0], $headers)->assertOk();
        $this->assertSame(1, \App\Models\ControlCalidad::count());
        $this->assertSame(1, \App\Models\Sancion::count());
    }

    public function test_un_acopiador_no_puede_registrar_inspecciones(): void
    {
        $productor = $this->productor();
        [,, $headers] = $this->autenticar(RolUsuario::ACOPIADOR);

        $this->postJson('/api/inspecciones', [
            'productorId' => $productor->id,
            'ph' => 6.1,
        ], $headers)->assertForbidden();
    }
}
