<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

// Un usuario por cada rol del ecosistema. Contraseña común de desarrollo:
// "milkflow2026". DNIs de ejemplo (8 dígitos).
class RolesUsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $base = [
            [RolUsuario::ACOPIADOR, '70000001', 'Mateo', 'Quispe Mamani'],
            [RolUsuario::ACOPIADOR, '70000007', 'Santiago', 'Paredes Coila'],
            [RolUsuario::SUPERVISOR_CALIDAD, '70000002', 'Rosa', 'Condori Apaza'],
            [RolUsuario::PRODUCTOR, '70000003', 'Julio', 'Cutipa Huanca'],
            [RolUsuario::JEFE_PRODUCCION, '70000004', 'Elena', 'Ramos Ticona'],
            [RolUsuario::DESPACHO_VENTAS, '70000005', 'Percy', 'Flores Choque'],
            [RolUsuario::ADMINISTRACION, '70000006', 'Ana', 'Vilca Sucari'],
            // Socios de la Ruta Sur con acceso a la app. El correo va explícito:
            // Elena Ramos (jefa de producción) ya ocupa elena@milkflow.pe.
            [RolUsuario::PRODUCTOR, '71000005', 'Elena', 'Quispe Apaza', 'elena.quispe@milkflow.pe'],
            [RolUsuario::PRODUCTOR, '71000006', 'Fermin', 'Condori Larico', 'fermin.condori@milkflow.pe'],
        ];

        foreach ($base as $fila) {
            [$rol, $dni, $nombres, $apellidos] = $fila;
            Usuario::updateOrCreate(
                ['dni' => $dni],
                [
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'email' => $fila[4] ?? strtolower(explode(' ', $nombres)[0]).'@milkflow.pe',
                    'telefono' => '95'.substr($dni, 2),
                    'password' => 'milkflow2026',
                    'rol' => $rol->value,
                    'activo' => true,
                ],
            );
        }
    }
}
