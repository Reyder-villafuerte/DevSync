<?php

namespace Tests\Feature\Panel;

use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Correlativo;
use App\Models\Dispositivo;
use App\Models\Producto;
use App\Models\Productor;
use App\Models\Ruta;
use App\Models\Usuario;
use App\Models\Zona;
use App\Services\Facturacion\VentaService;

/**
 * Helpers de construcción para las pruebas de los paneles web. Sigue el patrón
 * del proyecto (Model::create explícito) porque sólo existen factories de
 * Usuario y Producto.
 */
trait CreaEntidadesPanel
{
    protected function usuario(RolUsuario $rol): Usuario
    {
        return Usuario::factory()->rol($rol)->create();
    }

    protected function zona(?Ruta $ruta = null): Zona
    {
        $ruta ??= Ruta::create(['nombre' => 'Ruta '.uniqid(), 'codigo' => substr(uniqid(), -6)]);

        return Zona::create(['nombre' => 'Zona '.uniqid(), 'codigo' => substr(uniqid(), -6), 'ruta_id' => $ruta->id]);
    }

    protected function productor(?Zona $zona = null): Productor
    {
        $zona ??= $this->zona();

        return Productor::create([
            'codigo_padron' => 'P-'.uniqid(),
            'nombres' => 'Prod', 'apellidos' => 'Uctor '.uniqid(),
            'dni' => (string) random_int(70000000, 79999999),
            'zona_id' => $zona->id, 'estado' => 'activo',
            'fecha_ingreso' => now()->subYear()->toDateString(),
        ]);
    }

    protected function clientePublico(): Cliente
    {
        return Cliente::create([
            'nombre' => 'Mostrador '.uniqid(),
            'tipo_cliente' => 'publico',
            'activo' => true,
        ]);
    }

    /** Producto + tarifa de venta vigente para el tipo de cliente dado. */
    protected function productoConTarifa(string $tipoCliente = 'publico', float $precio = 18.0): Producto
    {
        $producto = Producto::factory()->create();

        \App\Models\PrecioVenta::create([
            'producto_id' => $producto->id,
            'tipo_cliente' => $tipoCliente,
            'precio' => $precio,
            'vigente_desde' => now()->subMonth()->toDateString(),
        ]);

        return $producto;
    }

    /** Dispositivo "Mostrador Web" + correlativo de boleta, como los deja MostradorWebSeeder. */
    protected function mostradorWeb(): Dispositivo
    {
        Correlativo::firstOrCreate(
            ['tipo_comprobante' => 'boleta', 'serie' => 'B001'],
            ['numero_maximo_asignado' => 0, 'tamano_bloque_default' => 50],
        );

        return Dispositivo::create([
            'usuario_id' => $this->usuario(RolUsuario::ADMINISTRACION)->id,
            'identificador' => VentaService::DISPOSITIVO_MOSTRADOR,
            'nombre' => 'Mostrador Web',
            'plataforma' => 'web',
            'activo' => true,
        ]);
    }
}
