<?php

namespace Tests\Feature;

use App\Models\ClientType;
use App\Models\Customer;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\User;
use App\Services\Ventas\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las pantallas de listado del sistema comparten el mismo patrón: tabla con
 * columna de ID, buscador, filtros y paginado de diez en diez. Aquí se cubre
 * lo que dejó de ser cosmético al adoptarlo.
 */
class TablasDelSistemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_el_resumen_de_caja_le_da_su_propia_fila_a_un_tipo_de_cliente_nuevo(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        $queso = Product::where('item_code', config('huata.codigos.queso'))->firstOrFail();

        // Un tipo que el administrador inventa después de instalado el sistema.
        $tipo = ClientType::create([
            'name' => 'Convenio municipalidad',
            'slug' => 'convenio-municipalidad',
            'min_quantity' => 1,
            'is_active' => true,
        ]);
        $tipo->productPrices()->create(['product_id' => $queso->id, 'price_per_unit' => 17.50]);

        $cliente = Customer::create([
            'first_name' => 'Municipalidad',
            'last_name' => 'de Huata',
            'type' => 'local',
            'client_type_id' => $tipo->id,
        ]);

        app(VentaService::class)->registrarVenta($vendedor, [
            'customer_id' => $cliente->id,
            'payment_method' => 'efectivo',
            'items' => [['product_id' => $queso->id, 'quantity' => 2]],
        ]);

        $respuesta = $this->actingAs($vendedor)->get('/ventas');

        $respuesta->assertStatus(200);
        // La venta cae en la fila del tipo nuevo y no se mezcla con «Cliente local».
        $respuesta->assertSee('Convenio municipalidad');
        $respuesta->assertSee('S/ 35.00');
    }

    public function test_el_flujo_de_caja_filtra_los_movimientos_por_tipo_en_el_servidor(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $acopiador = User::where('role', 'acopiador')->firstOrFail();

        OperationalExpense::create([
            'category' => 'pago_personal',
            'description' => 'Pago de turno del acopiador',
            'amount' => 280.00,
            'expense_date' => now()->toDateString(),
            'user_id' => $acopiador->id,
            'payment_method' => 'efectivo',
            'registered_by' => $admin->id,
        ]);

        $soloPersonal = $this->actingAs($admin)->get('/admin/finanzas?tipo=personal&period=todo');
        $soloPersonal->assertStatus(200);
        $soloPersonal->assertSee('Pago de turno del acopiador');

        // Al pedir solo ingresos, el egreso ya no viaja en la respuesta: el
        // filtro es del servidor, no un `display: none` del navegador.
        $soloIngresos = $this->actingAs($admin)->get('/admin/finanzas?tipo=ingreso&period=todo');
        $soloIngresos->assertStatus(200);
        $soloIngresos->assertDontSee('Pago de turno del acopiador');
    }

    public function test_el_flujo_de_caja_busca_por_concepto(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        OperationalExpense::create([
            'category' => 'operativo',
            'description' => 'Combustible de la camioneta',
            'amount' => 120.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'efectivo',
            'registered_by' => $admin->id,
        ]);

        OperationalExpense::create([
            'category' => 'operativo',
            'description' => 'Mantenimiento del tanque de frio',
            'amount' => 90.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'efectivo',
            'registered_by' => $admin->id,
        ]);

        $respuesta = $this->actingAs($admin)->get('/admin/finanzas?period=todo&buscar=combustible');

        $respuesta->assertStatus(200);
        $respuesta->assertSee('Combustible de la camioneta');
        $respuesta->assertDontSee('Mantenimiento del tanque de frio');
    }

    public function test_las_solicitudes_de_zona_se_pueden_buscar_por_productor(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $respuesta = $this->actingAs($admin)->get('/zonas/solicitudes?buscar=nombre-que-no-existe');

        $respuesta->assertStatus(200);
        $respuesta->assertSee('No se encontraron solicitudes con el filtro seleccionado.');
    }
}
