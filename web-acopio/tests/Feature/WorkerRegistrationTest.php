<?php

namespace Tests\Feature;

use App\Actions\ReviewWorker;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['nombres' => 'Juan', 'apellidos' => 'Pérez', 'documento' => '76543210', 'telefono' => '999999999', 'email' => 'juan@example.com', 'password' => 'MilkFlow!2026', 'password_confirmation' => 'MilkFlow!2026'];
    }

    public function test_registration_ignores_role_and_status_and_hashes_password(): void
    {
        $this->post('/register', $this->data() + ['role' => 'Administrador', 'status' => 'ACTIVO', 'approved_by' => 1])->assertRedirect('/registro/pendiente');
        $user = User::firstOrFail();
        $this->assertSame('PENDIENTE', $user->status);
        $this->assertCount(0, $user->roles);
        $this->assertNull($user->approved_by);
        $this->assertTrue(\Hash::check('MilkFlow!2026', $user->password));
        $this->assertDatabaseHas('auditoria', ['accion' => 'trabajador.registrado']);
        $this->post('/login', ['login' => $user->email, 'password' => 'MilkFlow!2026'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_duplicates_and_required_fields_are_rejected(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['nombres', 'apellidos', 'documento', 'telefono', 'email', 'password']);
        $this->post('/register', $this->data());
        $this->post('/register', $this->data())->assertSessionHasErrors(['email', 'documento']);
    }

    public function test_approval_preserves_password_and_redirects_to_assigned_role(): void
    {
        $this->post('/register', $this->data());
        $worker = User::firstOrFail();
        $hash = $worker->password;
        $admin = User::factory()->create(['status' => 'ACTIVO']);
        $role = Role::create(['name' => 'Administrador', 'slug' => 'admin']);
        $permission = Permission::create(['name' => 'users.manage']);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);
        $assigned = Role::create(['name' => 'Acopiador', 'slug' => 'acopiador']);
        $this->actingAs($admin);
        app(ReviewWorker::class)->execute($worker, 'aprobar', $assigned->id, null);
        $this->assertSame($hash, $worker->fresh()->password);
        $this->assertSame('ACTIVO', $worker->fresh()->status);
        auth()->logout();
        $this->post('/login', ['login' => $worker->email, 'password' => 'MilkFlow!2026'])->assertRedirect('/acopiador/dashboard');
    }

    public function test_rejected_and_inactive_cannot_login(): void
    {
        foreach (['RECHAZADO', 'INACTIVO'] as $status) {
            $u = User::factory()->create(['status' => $status, 'password' => 'MilkFlow!2026']);
            $this->post('/login', ['login' => $u->email, 'password' => 'MilkFlow!2026'])->assertSessionHasErrors('login');
            $this->assertGuest();
        }
    }
}
