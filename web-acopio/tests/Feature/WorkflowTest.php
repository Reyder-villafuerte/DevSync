<?php

namespace Tests\Feature;

use App\Models as M;
use App\Notifications\MilkFlowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function loginAs(string $role): M\User
    {
        $u = M\User::where('email', $role.'@milkflow.test')->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    public function test_admin_rejects_pending_worker_with_audited_reason(): void
    {
        Notification::fake();
        $worker = M\User::factory()->create(['status' => 'PENDIENTE']);
        $admin = $this->loginAs('admin');
        $this->post('/admin/solicitudes/'.$worker->id, ['decision' => 'rechazar'])->assertSessionHasErrors('motivo');
        $this->post('/admin/solicitudes/'.$worker->id, ['decision' => 'rechazar', 'motivo' => 'Documento no corresponde'])->assertRedirect('/admin/solicitudes');
        $this->assertSame('RECHAZADO', $worker->fresh()->status);
        $this->assertSame($admin->id, $worker->fresh()->rejected_by);
        $this->assertDatabaseHas('auditoria', ['accion' => 'trabajador.rechazar', 'registro' => $worker->id]);
        Notification::assertSentTo($worker, MilkFlowNotification::class);
    }

    public function test_admin_can_assign_each_official_role_with_original_password(): void
    {
        Notification::fake();
        foreach (M\Role::all() as $role) {
            $worker = M\User::factory()->create(['status' => 'PENDIENTE', 'password' => 'MilkFlow!2026']);
            $hash = $worker->password;
            $this->loginAs('admin');
            $this->post('/admin/solicitudes/'.$worker->id, ['decision' => 'aprobar', 'role_id' => $role->id])->assertRedirect('/admin/solicitudes');
            $this->assertSame($hash, $worker->fresh()->password);
            auth()->logout();
            $this->post('/login', ['login' => $worker->email, 'password' => 'MilkFlow!2026'])->assertRedirect('/'.$role->slug.'/dashboard');
            auth()->logout();
        }
    }

    public function test_catalog_edit_keeps_delivery_history_and_detail_renders(): void
    {
        $this->loginAs('admin');
        $p = M\Productor::firstOrFail();
        $count = $p->entregas()->count();
        $this->get('/admin/producers/'.$p->id.'/edit')->assertOk();
        $this->patch('/admin/producers/'.$p->id, ['nombre' => $p->nombre, 'documento' => $p->documento, 'telefono' => '988888888', 'sector_id' => $p->sector_id, 'user_id' => $p->user_id, 'activo' => 1])->assertRedirect('/admin/producers');
        $this->assertSame('988888888', $p->fresh()->telefono);
        $this->assertSame($count, $p->entregas()->count());
        $this->get('/admin/producers/'.$p->id)->assertOk();
    }

    public function test_producer_cannot_read_another_delivery_or_notification(): void
    {
        $user = $this->loginAs('productor');
        $other = M\Productor::where('id', '!=', $user->productor->id)->firstOrFail();
        $delivery = M\Entrega::firstOrFail()->replicate();
        $delivery->uuid = (string) \Str::uuid();
        $delivery->productor_id = $other->id;
        $delivery->save();
        $this->get('/productor/entregas/'.$delivery->id)->assertNotFound();
        $this->getJson('/api/v1/entregas')->assertOk()->assertJsonMissing(['uuid' => $delivery->uuid]);
    }

    public function test_reports_pdf_and_csv_and_secondary_screens(): void
    {
        $this->loginAs('admin');
        foreach (['/admin/reports', '/admin/settings', '/admin/rotaciones', '/notificaciones'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/reports?export=pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/admin/reports?export=csv')->assertOk();
        $this->loginAs('acopiador');
        foreach (['/acopiador/sync', '/acopiador/offline', '/acopiador/ruta/cerrar'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->loginAs('productor');
        $this->get('/productor/perfil')->assertOk();
    }

    public function test_api_issues_token_only_for_active_user_and_enforces_roles(): void
    {
        auth()->logout();
        $response = $this->postJson('/api/v1/login', ['login' => 'despacho', 'password' => 'MilkFlow!2026'])->assertOk();
        $token = $response->json('token');
        $this->withToken($token)->getJson('/api/v1/produccion')->assertForbidden();
        $u = M\User::where('email', 'despacho@milkflow.test')->firstOrFail();
        $u->forceFill(['status' => 'INACTIVO'])->save();
        $this->withToken($token)->getJson('/api/v1/notificaciones')->assertForbidden();
    }

    public function test_rotation_preserves_old_delivery_geography(): void
    {
        $this->loginAs('admin');
        $p = M\Productor::firstOrFail();
        $delivery = $p->entregas()->firstOrFail();
        $old = $delivery->zona_id;
        $sector = M\Sector::where('zona_id', '!=', $p->zona_id)->firstOrFail();
        $this->post('/admin/rotaciones', ['productor_id' => $p->id, 'sector_nuevo_id' => $sector->id, 'fecha_efectiva' => today()->toDateString(), 'referencia' => 'Cambio estacional'])->assertRedirect('/admin/rotaciones');
        $rotation = M\RotacionProductor::firstOrFail();
        $this->post('/admin/rotaciones/'.$rotation->id.'/revisar', ['decision' => 'APROBADA'])->assertRedirect();
        $this->assertEquals($sector->zona_id, $p->fresh()->zona_id);
        $this->assertEquals($old, $delivery->fresh()->zona_id);
        $this->assertEquals($old, $rotation->zona_anterior_id);
    }

    public function test_route_closure_blocks_new_delivery(): void
    {
        $this->loginAs('acopiador');
        $route = M\Ruta::firstOrFail();
        $this->post('/acopiador/ruta/cerrar', ['ruta_id' => $route->id])->assertOk();
        $this->post('/acopiador/entregas', ['uuid' => (string) \Str::uuid(), 'tipo' => 'RECOGIDA', 'ruta_id' => $route->id, 'productor_id' => M\Productor::firstOrFail()->id, 'litros' => 10])->assertSessionHasErrors('ruta_id');
    }
}
