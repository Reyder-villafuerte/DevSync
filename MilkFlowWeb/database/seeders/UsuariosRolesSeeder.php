<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios fijos del sistema: uno por cada rol operativo de Huata.
 *
 * Credencial estándar (web y móvil): DNI + contraseña `password`.
 * Los DNI están escritos en código para que `php artisan migrate:fresh --seed`
 * reconstruya siempre el mismo juego de accesos.
 */
class UsuariosRolesSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /**
     * Personal de planilla. El acopiador y el productor se numeran aparte
     * porque son varios por rol.
     *
     * [dni, nombre, rol, teléfono, correo]
     */
    public const PERSONAL = [
        ['70000001', 'Jefe General Huata',            'jefe_general',      '951000001', 'jefe@milkflow.com'],
        ['70000002', 'Admin Sistema Huata',           'admin',             '951000002', 'admin@milkflow.com'],
        ['70000003', 'Jefe Producción Planta',        'jefe_produccion',   '951000003', 'produccion@milkflow.com'],
        ['70000004', 'Encargado Liquidación y Pagos', 'personal_pago',     '951000004', 'pagos@milkflow.com'],
        ['70000005', 'Inspector Control Lactoscan',   'inspector_calidad', '951000005', 'calidad@milkflow.com'],
        ['70000006', 'Personal Venta y Despacho',     'personal_venta',    '951000006', 'ventas@milkflow.com'],
        ['70000007', 'Pagador de Sueldos en Ruta',    'pagador_campo',     '951000007', 'pagador@milkflow.com'],
    ];

    /** Los 5 acopiadores (4 zonas + 1 de descanso rotativo). */
    public const ACOPIADORES = [
        ['71110001', 'Carlos Quispe Acopiador',      '951111001', 'acopiador1@milkflow.com'],
        ['71110002', 'Manuel Mamani Acopiador',      '951111002', 'acopiador2@milkflow.com'],
        ['71110003', 'Raul Condori Acopiador',       '951111003', 'acopiador3@milkflow.com'],
        ['71110004', 'Pedro Flores Acopiador',       '951111004', 'acopiador4@milkflow.com'],
        ['71110005', 'David Choque (Turno Descanso)', '951111005', 'acopiador5@milkflow.com'],
    ];

    public function run(): void
    {
        $password = Hash::make(self::PASSWORD);

        foreach (self::PERSONAL as [$dni, $nombre, $rol, $telefono, $correo]) {
            User::updateOrCreate(['dni' => $dni], [
                'name' => $nombre,
                'email' => $correo,
                'password' => $password,
                'role' => $rol,
                'phone' => $telefono,
                'is_active' => true,
            ]);
        }

        foreach (self::ACOPIADORES as [$dni, $nombre, $telefono, $correo]) {
            User::updateOrCreate(['dni' => $dni], [
                'name' => $nombre,
                'email' => $correo,
                'password' => $password,
                'role' => 'acopiador',
                'phone' => $telefono,
                'is_active' => true,
            ]);
        }
    }
}
