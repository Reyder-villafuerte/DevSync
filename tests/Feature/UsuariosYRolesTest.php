<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zone;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Usuarios, roles y catálogo, cada uno en su pantalla.
 */
class UsuariosYRolesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->admin = User::where('role', 'admin')->firstOrFail();
    }

    // ------------------------------------------------------------ usuarios

    public function test_la_pantalla_de_usuarios_lista_el_padron(): void
    {
        $productor = User::where('role', 'productor')->firstOrFail();

        // La tabla pagina de a 10, así que se lo busca por su DNI.
        $this->actingAs($this->admin)
            ->get(route('admin.usuarios.index', ['buscar' => $productor->dni]))
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee($productor->name)
            ->assertSee('Agregar');
    }

    public function test_la_tabla_de_usuarios_pagina_de_diez_en_diez(): void
    {
        $total = User::count();
        $this->assertGreaterThan(10, $total, 'La semilla debería traer más de una página.');

        $respuesta = $this->actingAs($this->admin)->get(route('admin.usuarios.index'));

        $respuesta->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->count() === 10
                && $usuarios->total() === $total
                && $usuarios->currentPage() === 1)
            ->assertSee('Siguiente');

        // Y la segunda página trae gente distinta.
        $primeros = $respuesta->viewData('usuarios')->pluck('id');

        $this->actingAs($this->admin)
            ->get(route('admin.usuarios.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->currentPage() === 2
                && $usuarios->pluck('id')->intersect($primeros)->isEmpty());
    }

    public function test_se_crea_un_usuario_eligiendo_su_rol(): void
    {
        $zona = Zone::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Rosa Ccopa Mamani',
                'dni' => '45678912',
                'email' => 'rosa.ccopa@huata.pe',
                'role' => 'acopiador',
                'phone' => '951234567',
                'zone_id' => $zona->id,
                'password' => 'secreto123',
            ])
            ->assertSessionHas('success');

        $creada = User::where('dni', '45678912')->firstOrFail();

        $this->assertSame('acopiador', $creada->role);
        $this->assertSame($zona->id, $creada->zone_id);
        $this->assertTrue($creada->is_active);
        // La contraseña se guarda cifrada y sirve para entrar.
        $this->assertTrue(Hash::check('secreto123', $creada->password));
    }

    public function test_no_se_repite_el_dni_ni_el_correo(): void
    {
        $existente = User::where('role', 'productor')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Otro con el mismo DNI',
                'dni' => $existente->dni,
                'email' => 'otro@huata.pe',
                'role' => 'productor',
                'password' => 'secreto123',
            ])
            ->assertSessionHasErrors('dni');

        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Otro con el mismo correo',
                'dni' => '99887766',
                'email' => $existente->email,
                'role' => 'productor',
                'password' => 'secreto123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_editar_sin_contrasena_no_la_cambia(): void
    {
        $usuario = User::where('role', 'acopiador')->firstOrFail();
        $claveAntes = $usuario->password;

        $this->actingAs($this->admin)
            ->put(route('admin.usuarios.update', $usuario), [
                'name' => 'Nombre corregido',
                'dni' => $usuario->dni,
                'email' => $usuario->email,
                'role' => $usuario->role,
                'password' => '',
            ])
            ->assertSessionHas('success');

        $usuario->refresh();

        $this->assertSame('Nombre corregido', $usuario->name);
        $this->assertSame($claveAntes, $usuario->password);
    }

    public function test_se_le_puede_cambiar_el_rol_a_alguien(): void
    {
        $usuario = User::where('role', 'acopiador')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.usuarios.update', $usuario), [
                'name' => $usuario->name,
                'dni' => $usuario->dni,
                'email' => $usuario->email,
                'role' => 'inspector_calidad',
            ])
            ->assertSessionHas('success');

        $this->assertSame('inspector_calidad', $usuario->fresh()->role);
    }

    public function test_un_rol_inventado_se_rechaza(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Cargo que no existe',
                'dni' => '11223344',
                'email' => 'inventado@huata.pe',
                'role' => 'supervisor_galactico',
                'password' => 'secreto123',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_el_usuario_se_desactiva_en_vez_de_borrarse(): void
    {
        $usuario = User::where('role', 'productor')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.toggle', $usuario))
            ->assertSessionHas('success');

        $this->assertFalse($usuario->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $usuario->id]);

        // Y se puede volver a activar.
        $this->actingAs($this->admin)->post(route('admin.usuarios.toggle', $usuario));
        $this->assertTrue($usuario->fresh()->is_active);
    }

    public function test_nadie_se_desactiva_a_si_mismo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.toggle', $this->admin))
            ->assertSessionHasErrors('usuario');

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_se_filtra_por_rol(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.usuarios.index', ['rol' => 'acopiador']))
            ->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->total() > 0
                && collect($usuarios->items())->every(fn ($u) => $u->role === 'acopiador'));
    }

    // --------------------------------------------------------------- roles

    public function test_la_pantalla_de_roles_muestra_los_nueve_con_su_conteo(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Roles')
            ->assertSee('Productor de leche')
            ->assertSee('Pagador de campo')
            ->assertViewHas('roles', fn ($roles) => $roles->count() === 9
                && $roles['productor']['total'] === User::where('role', 'productor')->count());
    }

    public function test_los_roles_dicen_con_que_tipo_de_cliente_cobran(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Proveedor de leche');
    }

    // ------------------------------------------------------------ catálogo

    public function test_el_catalogo_resume_lo_que_hay_cargado(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.catalogo.index'))
            ->assertOk()
            ->assertSee('Catálogo del Sistema')
            ->assertSee('Insumos')
            ->assertSee('Productos terminados')
            ->assertSee('Tarifas de acopio')
            ->assertViewHas('fichas', fn ($fichas) => count($fichas) === 8);
    }

    // --------------------------------------------------------------- acceso

    public function test_el_acopiador_no_entra_a_ninguna_de_las_tres(): void
    {
        $acopiador = User::where('role', 'acopiador')->firstOrFail();

        foreach (['admin.usuarios.index', 'admin.roles.index', 'admin.catalogo.index'] as $ruta) {
            $this->actingAs($acopiador)->get(route($ruta))->assertForbidden();
        }
    }
}
