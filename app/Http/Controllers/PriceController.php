<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionPriceRule;
use App\Models\Supply;
use App\Models\SystemPrice;
use App\Services\Acopio\TarifaAcopioService;
use App\Services\Sistema\SistemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PriceController extends Controller
{
    /**
     * Panel de configuración de tarifas y precios de temporada
     */
    public function index()
    {
        abort_unless(Auth::user()->role === 'admin', 403);
        $currentPrice = SystemPrice::current();
        $history = SystemPrice::with('updater')->latest()->paginate(10);

        $tarifas = app(TarifaAcopioService::class);
        $insumosAcopiados = Supply::where('entry_mode', Supply::ENTRADA_ACOPIO)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $reglas = CollectionPriceRule::with('supply')
            ->whereIn('supply_id', $insumosAcopiados->pluck('id'))
            ->orderBy('supply_id')
            ->orderByDesc('priority')
            ->get();

        return view('admin.precios.index', compact('currentPrice', 'history', 'reglas', 'insumosAcopiados'));
    }

    /**
     * Actualizar precios de temporada
     */
    public function update(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        // Ni las tarifas de leche ni las de queso se escriben aquí: las
        // primeras salen de las reglas de acopio y las segundas del producto.
        // Esta acción solo cierra una temporada y deja constancia de lo que
        // regía ese día.
        $validated = $request->validate([
            'season_name' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $newPrice = app(SistemaService::class)
            ->actualizarTarifas(Auth::user(), $validated);

        return redirect()->route('admin.precios.index')->with('success', "Tarifas actualizadas correctamente para '{$newPrice->season_name}'.");
    }

    public function storeRegla(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $insumo = Supply::findOrFail($request->input('supply_id'));

        try {
            $regla = app(TarifaAcopioService::class)->crearRegla($insumo, $this->validarRegla($request));
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        app(TarifaAcopioService::class)->reflejarEnTarifasDelSistema();

        return back()->with('success', "Tarifa «{$regla->name}» creada.");
    }

    public function updateRegla(Request $request, CollectionPriceRule $regla)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $datos = $this->validarRegla($request);
        $datos['is_active'] = $request->boolean('is_active');

        try {
            $regla = app(TarifaAcopioService::class)->actualizarRegla($regla, $datos);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        app(TarifaAcopioService::class)->reflejarEnTarifasDelSistema();

        return back()->with('success', "Tarifa «{$regla->name}» actualizada.");
    }

    public function destroyRegla(CollectionPriceRule $regla)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $nombre = $regla->name;

        try {
            app(TarifaAcopioService::class)->eliminarRegla($regla);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        app(TarifaAcopioService::class)->reflejarEnTarifasDelSistema();

        return back()->with('success', "Tarifa «{$nombre}» eliminada.");
    }

    /** @return array<string, mixed> */
    private function validarRegla(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'metric' => ['nullable', 'string', 'max:50'],
            'operator' => ['nullable', 'string', 'in:'.implode(',', CollectionPriceRule::OPERADORES)],
            'threshold' => ['nullable', 'numeric'],
            'price_per_unit' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'applies_to_whole_cycle' => ['nullable', 'boolean'],
            'penalty_type' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
