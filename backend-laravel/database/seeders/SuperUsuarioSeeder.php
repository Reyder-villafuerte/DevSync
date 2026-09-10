<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Superusuario de administración, usable tanto en el panel web (sesión) como en
 * el móvil (token Sanctum). Idempotente.
 *
 *   DNI:         99999999
 *   Contraseña:  MilkFlow2026$
 *   Rol:         administracion
 *
 * Al sembrar imprime un token personal ('super-movil') para pruebas del cliente
 * móvil; si el token ya existe no se vuelve a emitir (guarde el que se imprimió
 * la primera vez).
 */
class SuperUsuarioSeeder extends Seeder
{
    public const DNI = '99999999';

    public const PASSWORD = 'MilkFlow2026$';

    public const TOKEN_MOVIL = 'super-movil';

    public function run(): void
    {
        $super = Usuario::updateOrCreate(
            ['dni' => self::DNI],
            [
                'nombres' => 'Super',
                'apellidos' => 'Administrador',
                'email' => 'super@milkflow.pe',
                'telefono' => '959999999',
                'password' => self::PASSWORD,
                'rol' => RolUsuario::ADMINISTRACION->value,
                'activo' => true,
            ],
        );

        $this->command?->info('Superusuario listo — DNI '.self::DNI.' / contraseña '.self::PASSWORD);

        if (! $super->tokens()->where('name', self::TOKEN_MOVIL)->exists()) {
            $token = $super->createToken(self::TOKEN_MOVIL, ['*'])->plainTextToken;
            $this->command?->warn('Token móvil del superusuario (guárdelo, no se vuelve a mostrar):');
            $this->command?->warn('  '.$token);
        } else {
            $this->command?->line('El token móvil del superusuario ya existía; no se re-emite.');
        }
    }
}
