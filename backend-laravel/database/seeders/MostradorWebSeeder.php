<?php

namespace Database\Seeders;

use App\Models\Dispositivo;
use App\Models\Usuario;
use App\Services\Facturacion\VentaService;
use Illuminate\Database\Seeder;

/**
 * Dispositivo lógico del punto de venta web. VentaService le reserva rangos de
 * correlativo (mismo motor que el canal móvil) para numerar los comprobantes de
 * mostrador. Idempotente; depende de SuperUsuarioSeeder.
 */
class MostradorWebSeeder extends Seeder
{
    public function run(): void
    {
        $responsable = Usuario::query()->where('dni', SuperUsuarioSeeder::DNI)->first()
            ?? Usuario::query()->where('rol', 'administracion')->first();

        if (! $responsable) {
            $this->command?->warn('No hay usuario administrador; ejecute SuperUsuarioSeeder primero.');

            return;
        }

        Dispositivo::updateOrCreate(
            ['identificador' => VentaService::DISPOSITIVO_MOSTRADOR],
            [
                'usuario_id' => $responsable->id,
                'nombre' => 'Mostrador Web',
                'plataforma' => 'web',
                'activo' => true,
            ],
        );

        $this->command?->info('Dispositivo «Mostrador Web» configurado para la facturación del panel.');
    }
}
