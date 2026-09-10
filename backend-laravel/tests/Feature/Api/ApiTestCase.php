<?php

namespace Tests\Feature\Api;

use App\Enums\RolUsuario;
use App\Models\Dispositivo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea usuario + dispositivo + token real (nombre disp:<uuid>, como en
     * AuthController) y devuelve las cabeceras Bearer.
     *
     * @return array{0: Usuario, 1: Dispositivo, 2: array<string,string>}
     */
    protected function autenticar(RolUsuario $rol, array $usuarioAttrs = []): array
    {
        $usuario = Usuario::factory()->rol($rol)->create($usuarioAttrs);
        $dispositivo = Dispositivo::create([
            'usuario_id' => $usuario->id,
            'identificador' => 'test-'.uniqid(),
            'plataforma' => 'android',
            'activo' => true,
        ]);
        $token = $usuario->createToken("disp:{$dispositivo->id}", ['rol:'.$rol->value])->plainTextToken;

        return [$usuario, $dispositivo, ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']];
    }
}
