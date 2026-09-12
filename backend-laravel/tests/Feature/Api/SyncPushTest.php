<?php

namespace Tests\Feature\Api;

use App\Enums\RolUsuario;
use App\Models\Acopiador;
use App\Models\Producto;
use App\Models\Productor;
use App\Models\RegistroAcopio;
use App\Models\Ruta;
use App\Models\RutaAcopio;
use App\Models\Zona;
use Illuminate\Support\Str;

class SyncPushTest extends ApiTestCase
{
    /** @return array{0:\App\Models\Usuario,1:array,2:RutaAcopio,3:Productor} */
    private function escenarioAcopio(): array
    {
        $ruta = Ruta::create(['nombre' => 'Ruta Norte', 'codigo' => '01']);
        $zona = Zona::create(['nombre' => 'Cabana', 'codigo' => 'cabana', 'ruta_id' => $ruta->id]);
        $productor = Productor::create([
            'codigo_padron' => 'P1', 'nombres' => 'N', 'apellidos' => 'A', 'dni' => '70000001',
            'zona_id' => $zona->id, 'estado' => 'activo', 'fecha_ingreso' => '2024-01-01',
        ]);

        [$usuario,, $headers] = $this->autenticar(RolUsuario::ACOPIADOR);
        $acopiador = Acopiador::create(['usuario_id' => $usuario->id, 'ruta_id' => $ruta->id, 'vigente_desde' => '2024-01-01']);
        $jornada = RutaAcopio::create([
            'acopiador_id' => $acopiador->id, 'ruta_id' => $ruta->id, 'fecha' => now()->toDateString(),
            'hora_inicio' => now(), 'estado' => 'en_curso',
        ]);

        return [$usuario, $headers, $jornada, $productor];
    }

    private function opRegistro(string $id, RutaAcopio $j, Productor $p, float $litros): array
    {
        return [
            'entidad' => 'registros_acopio',
            'id' => $id,
            'atributos' => [
                'rutaAcopioId' => $j->id,
                'productorId' => $p->id,
                'litros' => $litros,
                'horaRegistro' => now()->toISOString(),
            ],
        ];
    }

    public function test_push_es_idempotente_por_uuid(): void
    {
        [, $headers, $j, $p] = $this->escenarioAcopio();
        $id = (string) Str::uuid();
        $payload = ['operaciones' => [$this->opRegistro($id, $j, $p, 12.5)]];

        $this->postJson('/api/sync/push', $payload, $headers)->assertOk()
            ->assertJsonPath('aceptadas.0.resultado', 'insertado');

        // Reenvío del mismo lote: no duplica y responde idempotente.
        $this->postJson('/api/sync/push', $payload, $headers)->assertOk()
            ->assertJsonPath('aceptadas.0.resultado', 'idempotente');

        $this->assertSame(1, RegistroAcopio::where('id', $id)->count());
        $this->assertSame(1, RegistroAcopio::count());
    }

    public function test_recoleccion_reenviada_con_otro_contenido_es_conflicto(): void
    {
        [, $headers, $j, $p] = $this->escenarioAcopio();
        $id = (string) Str::uuid();

        $this->postJson('/api/sync/push', ['operaciones' => [$this->opRegistro($id, $j, $p, 12.5)]], $headers)->assertOk();

        $resp = $this->postJson('/api/sync/push', ['operaciones' => [$this->opRegistro($id, $j, $p, 99.0)]], $headers)
            ->assertStatus(207);

        $resp->assertJsonPath('conflictos.0.motivo', 'solo_insercion_no_actualizable');
        $resp->assertJsonPath('conflictos.0.servidor.litros', '12.50');
        // El servidor conserva el valor original.
        $this->assertEquals(12.5, (float) RegistroAcopio::find($id)->litros);
    }

    public function test_conflicto_de_version_en_la_cabecera_de_jornada(): void
    {
        [, $headers, $j] = $this->escenarioAcopio();

        // El servidor ya está en version >= 1. El cliente sube con versionBase 0
        // y contenido distinto => conflicto de versión con el estado del servidor.
        $resp = $this->postJson('/api/sync/push', ['operaciones' => [[
            'entidad' => 'rutas_acopio',
            'id' => $j->id,
            'versionBase' => 0,
            'atributos' => ['estado' => 'cerrada', 'litrosDeclarados' => 500],
        ]]], $headers)->assertStatus(207);

        $resp->assertJsonPath('conflictos.0.motivo', 'version_desactualizada');
        $this->assertSame('en_curso', $j->fresh()->estado);
    }

    /**
     * `rutas_acopio` tiene UNIQUE(acopiador_id, fecha). Un móvil que perdió su
     * caché abre una jornada nueva para un día que el servidor ya tiene: debe
     * volver como conflicto con la jornada buena, no como un error de
     * integridad que el acopiador no puede interpretar.
     */
    public function test_segunda_jornada_del_mismo_dia_es_conflicto_con_la_del_servidor(): void
    {
        config(['sync.jornada_unica_por_dia' => true]);
        [, $headers, $j] = $this->escenarioAcopio();
        $j->forceFill(['estado' => 'conciliada'])->save();

        $idNuevo = (string) Str::uuid();
        $resp = $this->postJson('/api/sync/push', ['operaciones' => [[
            'entidad' => 'rutas_acopio',
            'id' => $idNuevo,
            'atributos' => [
                'rutaId' => $j->ruta_id,
                'fecha' => $j->fecha->toDateString(),
                'horaInicio' => now()->toISOString(),
                'estado' => 'en_curso',
            ],
        ]]], $headers)->assertStatus(207);

        $resp->assertJsonPath('conflictos.0.motivo', 'jornada_del_dia_ya_existe');
        $resp->assertJsonPath('conflictos.0.servidor.id', $j->id);
        $this->assertNull(RutaAcopio::find($idNuevo));
        $this->assertSame(1, RutaAcopio::count());
    }

    /** Fase de pruebas: con la regla apagada el mismo día admite varias rutas. */
    public function test_con_la_regla_apagada_se_acepta_otra_jornada_del_mismo_dia(): void
    {
        config(['sync.jornada_unica_por_dia' => false]);
        [, $headers, $j] = $this->escenarioAcopio();

        $idNuevo = (string) Str::uuid();
        $this->postJson('/api/sync/push', ['operaciones' => [[
            'entidad' => 'rutas_acopio',
            'id' => $idNuevo,
            'atributos' => [
                'rutaId' => $j->ruta_id,
                'fecha' => $j->fecha->toDateString(),
                'horaInicio' => now()->toISOString(),
                'estado' => 'en_curso',
            ],
        ]]], $headers)->assertOk()->assertJsonPath('aceptadas.0.resultado', 'insertado');

        $this->assertSame(2, RutaAcopio::count());
    }

    /**
     * Una hora enviada en UTC debe guardarse como ese mismo instante. Antes se
     * escribía el reloj de pared sin zona y PostgreSQL lo leía como hora local:
     * +5 h en cada subida, acumulándose en cada re-sincronización.
     */
    public function test_la_hora_enviada_en_utc_no_se_desplaza(): void
    {
        [, $headers, $j, $p] = $this->escenarioAcopio();
        $instante = now()->setTimezone('UTC')->startOfSecond();

        $this->postJson('/api/sync/push', ['operaciones' => [[
            'entidad' => 'registros_acopio',
            'id' => (string) Str::uuid(),
            'atributos' => [
                'rutaAcopioId' => $j->id,
                'productorId' => $p->id,
                'litros' => 10,
                'horaRegistro' => $instante->toIso8601String(),
            ],
        ]]], $headers)->assertOk();

        $guardada = RegistroAcopio::query()->latest('created_at')->first()->hora_registro;
        $this->assertTrue(
            $instante->equalTo($guardada),
            "Se esperaba {$instante->toIso8601String()} y se guardó {$guardada->toIso8601String()}",
        );
    }

    public function test_movimientos_de_stock_son_conmutativos_e_idempotentes(): void
    {
        $producto = Producto::factory()->create();
        [$usuario, , $headers] = $this->autenticar(RolUsuario::JEFE_PRODUCCION);

        $mkOp = fn (string $id, float $c) => [
            'entidad' => 'movimientos_stock',
            'id' => $id,
            'atributos' => [
                'productoId' => $producto->id,
                'tipoMovimiento' => 'produccion_ingreso',
                'cantidad' => $c,
                'registradoPor' => $usuario->id,
                'ocurridoEn' => now()->toISOString(),
            ],
        ];
        $id1 = (string) Str::uuid();
        $id2 = (string) Str::uuid();

        // Orden A luego B, y luego B luego A reenviado: mismo saldo, sin duplicar.
        $this->postJson('/api/sync/push', ['operaciones' => [$mkOp($id1, 10), $mkOp($id2, 5)]], $headers)->assertOk();
        $this->postJson('/api/sync/push', ['operaciones' => [$mkOp($id2, 5), $mkOp($id1, 10)]], $headers)->assertOk();

        $this->assertSame(2, \App\Models\MovimientoStock::count());
        $this->assertEquals(15.0, app(\App\Services\Stock\StockService::class)->cantidadActual($producto->id));
    }
}
