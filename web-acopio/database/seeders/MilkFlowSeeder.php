<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models as M;
use App\Services\DeliveryService;
use App\Services\ProductionService;
use App\Services\QualityService;
use App\Services\SalesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MilkFlowSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.manage',
            'catalogs.manage',
            'entregas.create',
            'quality.manage',
            'production.manage',
            'sales.manage',
            'settlements.manage',
            'announcements.manage',
            'rotation.request',
            'audit.view',
            'reports.view',
        ];

        foreach ($permissions as $p) {
            M\Permission::firstOrCreate([
                'name' => $p,
            ]);
        }

        $map = [
            'admin' => $permissions,
            'acopiador' => ['entregas.create'],
            'supervisor' => ['quality.manage', 'reports.view'],
            'produccion' => ['production.manage', 'reports.view'],
            'despacho' => ['sales.manage', 'reports.view'],
            'productor' => ['rotation.request'],
        ];

        foreach (Role::cases() as $i => $enum) {
            $role = M\Role::firstOrCreate(
                ['slug' => $enum->slug()],
                ['name' => $enum->value]
            );

            $role->permissions()->sync(
                M\Permission::whereIn(
                    'name',
                    $map[$enum->slug()]
                )->pluck('id')
            );

            $u = M\User::firstOrCreate(
                [
                    'email' => $enum->slug() . '@milkflow.test',
                ],
                [
                    'name' => $enum->value . ' Demo',
                    'nombres' => $enum->slug() === 'admin'
                        ? 'Heiner'
                        : 'Reyder',
                    'apellidos' => 'MilkFlow',
                    'documento' => (string) (80000001 + $i),
                    'telefono' => '99900000' . ($i + 1),
                    'username' => $enum->slug(),
                    'password' => Hash::make('MilkFlow!2026'),
                ]
            );

            if ($u->wasRecentlyCreated) {
                $u->forceFill([
                    'status' => 'ACTIVO',
                    'approved_at' => now(),
                ])->save();

                $u->roles()->sync([
                    $role->id,
                ]);
            }
        }

        foreach (
            [
                'hora_inicio' => '04:30',
                'hora_fin' => '12:00',
                'tarifa_normal' => '1.70',
                'tarifa_castigo' => '0.65',
                'descuento_reincidencia' => '10.00',
            ] as $key => $value
        ) {
            DB::table('configuraciones')->insertOrIgnore([
                'clave' => $key,
                'valor' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $district = M\Distrito::firstOrCreate([
            'nombre' => 'HUARI',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $zone = M\Zona::firstOrCreate(
                [
                    'nombre' => 'Zona ' . $i,
                ],
                [
                    'distrito_id' => $district->id,
                ]
            );

            for ($j = 1; $j <= 2; $j++) {
                M\Sector::firstOrCreate([
                    'zona_id' => $zone->id,
                    'nombre' => 'Sector ' . $i . '.' . $j,
                ]);
            }
        }

        $collectorUser = M\User::where(
            'email',
            'acopiador@milkflow.test'
        )->firstOrFail();

        $collector = M\Acopiador::firstOrCreate(
            [
                'user_id' => $collectorUser->id,
            ],
            [
                'nombre' => $collectorUser->name,
                'telefono' => $collectorUser->telefono,
            ]
        );

        $route = M\Ruta::firstOrCreate(
            [
                'codigo' => 'R-01',
            ],
            [
                'nombre' => 'Ruta 01 · Huari',
                'vehiculo' => 'Camión 5T',
                'acopiador_id' => $collector->id,
            ]
        );

        $route->sectores()->syncWithoutDetaching(
            M\Sector::orderBy('id')
                ->limit(3)
                ->pluck('id')
        );

        $producerUser = M\User::where(
            'email',
            'productor@milkflow.test'
        )->firstOrFail();

        $sector = M\Sector::firstOrFail();

        $producer = M\Productor::firstOrCreate(
            [
                'documento' => $producerUser->documento,
            ],
            [
                'user_id' => $producerUser->id,
                'nombre' => 'Reyder Villafuerte',
                'telefono' => $producerUser->telefono,
                'sector_id' => $sector->id,
                'zona_id' => $sector->zona_id,
                'distrito_id' => $district->id,
            ]
        );

        foreach (
            [
                'Heiner Apaza',
                'Elena Pérez',
                'José Quispe',
            ] as $i => $name
        ) {
            M\Productor::firstOrCreate(
                [
                    'documento' => (string) (70000001 + $i),
                ],
                [
                    'nombre' => $name,
                    'telefono' => '99910000' . ($i + 1),
                    'sector_id' => $sector->id,
                    'zona_id' => $sector->zona_id,
                    'distrito_id' => $district->id,
                ]
            );
        }

        foreach (
            [
                'Queso para fresco',
                'Queso para pasteurizado',
                'Modulado',
            ] as $type
        ) {
            M\StockQueso::firstOrCreate(
                [
                    'tipo_producto' => $type,
                ],
                [
                    'cantidad' => 0,
                ]
            );
        }

        $admin = M\User::where(
            'email',
            'admin@milkflow.test'
        )->firstOrFail();

        M\Comunicado::firstOrCreate(
            [
                'titulo' => 'Bienvenidos a MilkFlow',
            ],
            [
                'mensaje' => 'El acopio inicia a las 04:30. Mantén tus recipientes limpios y la leche protegida del sol.',
                'fecha' => today(),
                'autor' => $admin->id,
            ]
        );

        M\Temporada::firstOrCreate(
            [
                'nombre' => 'Temporada 2026',
            ],
            [
                'inicio' => '2026-01-01',
                'fin' => '2026-12-31',
            ]
        );

        if (!M\Entrega::exists()) {
            Auth::login($collectorUser);

            $delivery = app(DeliveryService::class)->create(
                [
                    'uuid' => (string) Str::uuid(),
                    'productor_id' => $producer->id,
                    'tipo' => 'RECOGIDA',
                    'ruta_id' => $route->id,
                    'litros' => 500,
                ],
                $collectorUser
            );

            $supervisor = M\User::where(
                'email',
                'supervisor@milkflow.test'
            )->firstOrFail();

            Auth::login($supervisor);

            app(QualityService::class)->create(
                [
                    'entrega_id' => $delivery->id,
                    'ph' => 6.7,
                    'temperatura' => 4,
                    'agua_agregada_porcentaje' => 0,
                ],
                $supervisor
            );

            $production = M\User::where(
                'email',
                'produccion@milkflow.test'
            )->firstOrFail();

            Auth::login($production);

            app(ProductionService::class)->create(
                [
                    'codigo_lote' => 'DEMO-001',
                    'tipo_producto' => 'Queso para fresco',
                    'litros_leche' => 200,
                    'moldes_obtenidos' => 24,
                ],
                $production
            );

            $sales = M\User::where(
                'email',
                'despacho@milkflow.test'
            )->firstOrFail();

            Auth::login($sales);

            app(SalesService::class)->create(
                [
                    'uuid' => (string) Str::uuid(),
                    'cliente' => 'Cliente de demostración',
                    'tipo_cliente' => 'Mayorista',
                    'stock_queso_id' => M\StockQueso::firstOrFail()->id,
                    'cantidad' => 3,
                ],
                $sales
            );

            Auth::logout();
        }
    }
}
