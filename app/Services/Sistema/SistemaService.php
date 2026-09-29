<?php

namespace App\Services\Sistema;

use App\Exceptions\ReglaNegocioException;
use App\Models\Announcement;
use App\Models\ClientType;
use App\Models\ClientTypeRole;
use App\Models\CollectionPriceRule;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\SystemPrice;
use App\Models\User;
use App\Services\Acopio\TarifaAcopioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Configuración del sistema: tarifas de temporada, avisos de login y
 * egresos operativos del flujo de caja.
 */
class SistemaService
{
    /**
     * Las tarifas se historizan: la vigente se desactiva y se crea una nueva fila
     * activa, de modo que un recibo antiguo siempre puede rastrear su precio.
     */
    public function actualizarTarifas(User $autor, array $datos, ?string $clientUuid = null): SystemPrice
    {
        $tarifasDeQueso = $this->tarifasDeQueso($datos);
        $tarifasDeLeche = $this->tarifasDeLeche($datos);

        return DB::transaction(function () use ($autor, $datos, $clientUuid, $tarifasDeQueso, $tarifasDeLeche) {
            SystemPrice::where('is_active', true)->lockForUpdate()->get();
            SystemPrice::where('is_active', true)->update(['is_active' => false]);

            $tarifas = SystemPrice::create([
                'client_uuid' => $clientUuid,
                'season_name' => $datos['season_name'],
                'price_milk_base' => $tarifasDeLeche['base'],
                'price_milk_water_penalty_low' => $tarifasDeLeche['penalidad_leve'],
                'price_milk_water_penalty_high' => $tarifasDeLeche['penalidad_grave'],
                'price_cheese_provider' => $tarifasDeQueso['price_provider'],
                'price_cheese_wholesale' => $tarifasDeQueso['price_wholesale'],
                'price_cheese_local' => $tarifasDeQueso['price_local'],
                'is_active' => true,
                'updated_by' => $autor->id,
                'notes' => $datos['notes'] ?? 'Ajuste de tarifas de temporada.',
            ]);

            $this->propagarTarifasDeQueso($tarifas);
            $this->propagarTarifasDeLeche($datos);

            return $tarifas;
        });
    }

    /**
     * Qué tarifas de leche se historizan.
     *
     * La pantalla de Precios ya no las pide: lo que se le paga al productor
     * sale de las reglas de acopio. Se copian de ahí para que la fila
     * histórica diga la verdad. Si quien llama sí las manda —la app móvil ya
     * instalada lo sigue haciendo— manda lo enviado.
     *
     * @param  array<string, mixed>  $datos
     * @return array{base: float, penalidad_leve: float, penalidad_grave: float}
     */
    private function tarifasDeLeche(array $datos): array
    {
        $vigentes = SystemPrice::current();

        if (isset($datos['price_milk_base'], $datos['price_milk_water_penalty_low'], $datos['price_milk_water_penalty_high'])) {
            return [
                'base' => (float) $datos['price_milk_base'],
                'penalidad_leve' => (float) $datos['price_milk_water_penalty_low'],
                'penalidad_grave' => (float) $datos['price_milk_water_penalty_high'],
            ];
        }

        $tarifas = app(TarifaAcopioService::class);
        $leche = $tarifas->leche();

        if (! $leche) {
            return [
                'base' => (float) $vigentes->price_milk_base,
                'penalidad_leve' => (float) $vigentes->price_milk_water_penalty_low,
                'penalidad_grave' => (float) $vigentes->price_milk_water_penalty_high,
            ];
        }

        $reglas = $tarifas->reglasDe($leche);

        return [
            'base' => $tarifas->precioBase($leche),
            'penalidad_leve' => (float) ($reglas->firstWhere('penalty_type', 'leve_descuento')->price_per_unit
                ?? $vigentes->price_milk_water_penalty_low),
            'penalidad_grave' => (float) ($reglas->firstWhere('penalty_type', 'grave_expulsion')->price_per_unit
                ?? $vigentes->price_milk_water_penalty_high),
        ];
    }

    /**
     * Si las tarifas de leche vinieron de afuera (el móvil), bajan a las
     * reglas de acopio, que son las que de verdad liquidan.
     *
     * @param  array<string, mixed>  $datos
     */
    private function propagarTarifasDeLeche(array $datos): void
    {
        if (! isset($datos['price_milk_base'])) {
            return;
        }

        $leche = app(TarifaAcopioService::class)->leche();

        if (! $leche) {
            return;
        }

        CollectionPriceRule::where('supply_id', $leche->id)->whereNull('metric')
            ->update(['price_per_unit' => $datos['price_milk_base']]);

        foreach (['leve_descuento' => 'price_milk_water_penalty_low', 'grave_expulsion' => 'price_milk_water_penalty_high'] as $penalidad => $campo) {
            if (isset($datos[$campo])) {
                CollectionPriceRule::where('supply_id', $leche->id)
                    ->where('penalty_type', $penalidad)
                    ->update(['price_per_unit' => $datos[$campo]]);
            }
        }
    }

    /**
     * Qué tarifas de queso se historizan junto con las de la leche.
     *
     * La pantalla de Precios ya no las pide: el precio de venta de cualquier
     * producto se pone en Productos y Recetas. Así que se copian del producto
     * del catálogo, para que la fila histórica siga diciendo la verdad de lo
     * que se cobraba ese día.
     *
     * Si quien llama sí las manda —la app móvil vieja lo sigue haciendo— manda
     * lo que mandó, y de ahí baja al producto.
     *
     * @param  array<string, mixed>  $datos
     * @return array{price_provider: float, price_wholesale: float, price_local: float}
     */
    private function tarifasDeQueso(array $datos): array
    {
        if (isset($datos['price_cheese_provider'], $datos['price_cheese_wholesale'], $datos['price_cheese_local'])) {
            return [
                'price_provider' => (float) $datos['price_cheese_provider'],
                'price_wholesale' => (float) $datos['price_cheese_wholesale'],
                'price_local' => (float) $datos['price_cheese_local'],
            ];
        }

        $queso = Product::where('item_code', config('huata.codigos.queso'))->first();
        $vigentes = SystemPrice::current();

        return [
            'price_provider' => (float) ($queso->price_provider ?? $vigentes->price_cheese_provider),
            'price_wholesale' => (float) ($queso->price_wholesale ?? $vigentes->price_cheese_wholesale),
            'price_local' => (float) ($queso->price_local ?? $vigentes->price_cheese_local),
        ];
    }

    /**
     * El queso del catálogo se queda con la tarifa que se acaba de historizar.
     *
     * Cuando el precio vino del propio producto esto no cambia nada; cuando
     * vino de afuera (el móvil), lo deja alineado.
     */
    private function propagarTarifasDeQueso(SystemPrice $tarifas): void
    {
        $queso = Product::where('item_code', config('huata.codigos.queso'))->first();

        if (! $queso) {
            return;
        }

        $queso->update([
            'price_provider' => $tarifas->price_cheese_provider,
            'price_wholesale' => $tarifas->price_cheese_wholesale,
            'price_local' => $tarifas->price_cheese_local,
        ]);

        $queso->syncTarifasBase();
    }

    /**
     * Da de alta un tipo de cliente con su tarifa estándar.
     *
     * El slug se fija al crear y ya no cambia: los tres sembrados
     * (proveedor, mayorista, local) son la bisagra con la columna `type` de
     * los clientes viejos y con las tres columnas heredadas del producto.
     *
     * @param  array{name: string, min_quantity?: float, auto_role?: string|null, description?: string|null}  $datos
     */
    public function crearTipoCliente(array $datos): ClientType
    {
        $nombre = trim($datos['name'] ?? '');

        if ($nombre === '') {
            throw new ReglaNegocioException('El tipo de cliente necesita un nombre.', 'name');
        }

        $slug = Str::slug($nombre);

        if (ClientType::where('slug', $slug)->exists()) {
            throw new ReglaNegocioException("Ya existe un tipo de cliente llamado {$nombre}.", 'name');
        }

        $roles = $this->rolesPedidos($datos);
        $this->verificarRolesLibres($roles, null);

        $tipo = ClientType::create([
            'name' => $nombre,
            'slug' => $slug,
            'min_quantity' => max(0, (float) ($datos['min_quantity'] ?? 0)),
            'description' => $datos['description'] ?? null,
            'is_active' => true,
        ]);

        $this->reemplazarRoles($tipo, $roles);

        return $tipo->load('roles');
    }

    /** @param  array<string, mixed>  $datos */
    public function actualizarTipoCliente(ClientType $tipo, array $datos): ClientType
    {
        $nombre = trim($datos['name'] ?? '');

        if ($nombre === '') {
            throw new ReglaNegocioException('El tipo de cliente necesita un nombre.', 'name');
        }

        $roles = $this->rolesPedidos($datos);
        $this->verificarRolesLibres($roles, $tipo->id);

        // El slug no se toca: renombrar «Mayorista» no debe romper las tarifas
        // que ya cuelgan de él.
        $tipo->update([
            'name' => $nombre,
            'min_quantity' => max(0, (float) ($datos['min_quantity'] ?? 0)),
            'description' => $datos['description'] ?? null,
            'is_active' => (bool) ($datos['is_active'] ?? true),
        ]);

        $this->reemplazarRoles($tipo, $roles);

        return $tipo->load('roles');
    }

    /** Solo se borra un tipo que nadie esté usando. */
    public function eliminarTipoCliente(ClientType $tipo): void
    {
        $clientes = $tipo->customers()->count();

        if ($clientes > 0) {
            throw new ReglaNegocioException(
                "«{$tipo->name}» tiene {$clientes} cliente(s) asignados. Desactívalo o muévelos antes.",
                'tipo'
            );
        }

        if (ClientType::where('is_active', true)->count() <= 1) {
            throw new ReglaNegocioException(
                'Tiene que quedar al menos un tipo de cliente: es la tarifa con la que se cobra por defecto.',
                'tipo'
            );
        }

        $tipo->delete();
    }

    /**
     * Los roles que el formulario pide reconocer, sin repetidos ni inventados.
     *
     * @param  array<string, mixed>  $datos
     * @return array<int, string>
     */
    private function rolesPedidos(array $datos): array
    {
        $conocidos = array_keys(config('huata.roles'));

        return collect($datos['auto_roles'] ?? [])
            ->map(fn ($rol) => trim((string) $rol))
            ->filter(fn ($rol) => $rol !== '' && in_array($rol, $conocidos, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Dos tipos no pueden reclamar el mismo rol: la tarifa sería ambigua.
     *
     * @param  array<int, string>  $roles
     */
    private function verificarRolesLibres(array $roles, ?int $exceptoId): void
    {
        if (empty($roles)) {
            return;
        }

        $ocupado = ClientTypeRole::with('clientType')
            ->whereIn('role', $roles)
            ->when($exceptoId, fn ($q) => $q->where('client_type_id', '!=', $exceptoId))
            ->first();

        if ($ocupado) {
            throw new ReglaNegocioException(
                "El rol {$ocupado->etiqueta()} ya está tomado por «{$ocupado->clientType->name}»: ".
                'un rol solo puede dar una tarifa.',
                'auto_roles'
            );
        }
    }

    /** @param  array<int, string>  $roles */
    private function reemplazarRoles(ClientType $tipo, array $roles): void
    {
        $tipo->roles()->delete();

        foreach ($roles as $rol) {
            $tipo->roles()->create(['role' => $rol]);
        }
    }

    public function publicarAviso(User $autor, array $datos, ?string $clientUuid = null): Announcement
    {
        return Announcement::create([
            'client_uuid' => $clientUuid,
            'title' => $datos['title'],
            'message' => $datos['message'],
            'start_date' => $datos['start_date'],
            'end_date' => $datos['end_date'],
            'target_role' => $datos['target_role'] ?: null,
            'target_user_id' => $datos['target_user_id'] ?: null,
            'created_by' => $autor->id,
            'is_active' => true,
        ]);
    }

    public function registrarEgreso(User $registrador, array $datos, ?string $clientUuid = null): OperationalExpense
    {
        $beneficiario = $datos['beneficiary_name'] ?? null;

        if (! empty($datos['user_id']) && empty($beneficiario)) {
            $personal = User::find($datos['user_id']);
            $beneficiario = $personal?->name;
        }

        return OperationalExpense::create([
            'client_uuid' => $clientUuid,
            'category' => $datos['category'],
            'description' => $datos['description'],
            'amount' => $datos['amount'],
            'expense_date' => $datos['expense_date'],
            'user_id' => $datos['user_id'] ?? null,
            'beneficiary_name' => $beneficiario,
            'payment_method' => $datos['payment_method'],
            'receipt_number' => $datos['receipt_number'] ?? null,
            'registered_by' => $registrador->id,
            'notes' => $datos['notes'] ?? null,
        ]);
    }
}
