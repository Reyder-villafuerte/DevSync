<?php

namespace App\Services\Sistema;

use App\Models\Announcement;
use App\Models\OperationalExpense;
use App\Models\SystemPrice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($autor, $datos, $clientUuid) {
            SystemPrice::where('is_active', true)->update(['is_active' => false]);

            return SystemPrice::create([
                'client_uuid' => $clientUuid,
                'season_name' => $datos['season_name'],
                'price_milk_base' => $datos['price_milk_base'],
                'price_milk_water_penalty_low' => $datos['price_milk_water_penalty_low'],
                'price_milk_water_penalty_high' => $datos['price_milk_water_penalty_high'],
                'price_cheese_provider' => $datos['price_cheese_provider'],
                'price_cheese_wholesale' => $datos['price_cheese_wholesale'],
                'price_cheese_local' => $datos['price_cheese_local'],
                'is_active' => true,
                'updated_by' => $autor->id,
                'notes' => $datos['notes'] ?? 'Ajuste de tarifas de temporada.',
            ]);
        });
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

        if (!empty($datos['user_id']) && empty($beneficiario)) {
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
