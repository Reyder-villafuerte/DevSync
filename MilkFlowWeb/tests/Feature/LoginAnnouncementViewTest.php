<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAnnouncementViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_announcements_restored_as_session_arrays(): void
    {
        $this->seed(\Database\Seeders\MilkFlowHuataSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['login_announcements' => [[
                'title' => 'Aviso de prueba de sesión',
                'message' => 'Contenido conservado al volver del login.',
                'start_date' => '2026-09-15',
                'end_date' => '2026-09-22',
            ]]])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Aviso de prueba de sesión')
            ->assertSee('Contenido conservado al volver del login.')
            ->assertSee('2026-09-22');
    }
}
