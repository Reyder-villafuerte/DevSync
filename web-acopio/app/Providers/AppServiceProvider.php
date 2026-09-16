<?php

namespace App\Providers;

use App\Services\DisplayValue;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(DisplayValue::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        ResetPassword::toMailUsing(function ($user, string $token) {
            return (new MailMessage)
                ->subject('Restablecer contraseña · MilkFlow')
                ->greeting('Hola, '.$user->nombres)
                ->line('Recibimos una solicitud para restablecer tu contraseña de MilkFlow.')
                ->action('Restablecer contraseña', route('password.reset', ['token' => $token, 'email' => $user->email]))
                ->line('Este enlace vence en '.config('auth.passwords.users.expire').' minutos y solo puede utilizarse una vez.')
                ->line('Si no solicitaste este cambio, puedes ignorar este mensaje.')
                ->salutation('El equipo de MilkFlow');
        });
    }
}
