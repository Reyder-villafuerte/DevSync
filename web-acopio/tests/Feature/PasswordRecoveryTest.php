<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_changes_password_preserves_role_and_consumes_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'ACTIVO', 'password' => 'Original!2026']);
        $role = Role::create(['name' => 'Acopiador', 'slug' => 'acopiador']);
        $user->roles()->attach($role);
        $this->post('/auth/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token, $user) {
            $token = $notification->token;
            $mail = $notification->toMail($user);
            $this->assertStringContainsString('/reset-password/'.$token, $mail->actionUrl);
            $this->assertStringContainsString('MilkFlow', (string) $mail->render());

            return true;
        });
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'Renovada!2026', 'password_confirmation' => 'Renovada!2026'];
        $this->post('/reset-password', $data)->assertRedirect('/login')->assertSessionHas('status');
        $this->assertTrue(Hash::check('Renovada!2026', $user->fresh()->password));
        $this->assertSame('ACTIVO', $user->fresh()->status);
        $this->assertTrue($user->fresh()->hasRole('acopiador'));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $this->post('/login', ['login' => $user->email, 'password' => 'Original!2026'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => $user->email, 'password' => 'Renovada!2026'])->assertRedirect('/acopiador/dashboard');
    }

    public function test_invalid_expired_tokens_and_weak_passwords_do_not_change_password(): void
    {
        $user = User::factory()->create(['password' => 'Original!2026']);
        $hash = $user->password;
        $data = ['email' => $user->email, 'token' => 'invalid', 'password' => 'Renovada!2026', 'password_confirmation' => 'Renovada!2026'];
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $data['token'] = Password::createToken($user);
        $this->post('/reset-password', array_replace($data, ['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post('/reset-password', array_replace($data, ['password_confirmation' => 'Different!2026']))->assertSessionHasErrors('password');
        $this->travel(61)->minutes();
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_unknown_email_does_not_disclose_accounts(): void
    {
        Notification::fake();
        $this->post('/auth/forgot-password', ['email' => 'unknown@example.test'])->assertSessionHas('status')->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    }
}
