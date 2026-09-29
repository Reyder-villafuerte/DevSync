<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\ProducerSettlement;
use App\Models\Purchase;
use App\Models\Supply;
use App\Services\Produccion\ComprasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Compras de insumos: a quién se le compró, qué, cuánto y a qué precio.
 *
 * Es la pantalla que le da origen al costo del almacén; el stock que mueve se
 * ve en {@see CatalogoAlmacenController}.
 */
class ComprasController extends Controller
{
    public const ROLES_PERMITIDOS = ['jefe_produccion', 'admin', 'jefe_general'];

    public function __construct(private ComprasService $compras) {}

    public function index(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $compras = Purchase::with(['supplier', 'items.supply', 'registrar'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = $request->buscar;

                $q->where('document_number', 'like', "%{$termino}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$termino}%"));
            })
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'pagina_compras')
            ->withQueryString();

        // Solo lo que de verdad se compra: la leche entra por acopio.
        $insumos = Supply::where('is_active', true)
            ->where('entry_mode', Supply::ENTRADA_COMPRA)
            ->orderBy('name')
            ->get();

        // Lo que la planta le paga a los pobladores por su leche no pasa por
        // aquí, pero es parte de lo que cuesta el almacén: se muestra al lado
        // para que el total del mes no engañe.
        $liquidacionesDelMes = ProducerSettlement::whereBetween('end_date', [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
        ])->get();

        $leche = [
            'litros' => (float) $liquidacionesDelMes->sum('total_liters'),
            'importe' => (float) $liquidacionesDelMes->sum('gross_total'),
        ];

        // El resumen mira todo el historial, no solo la página visible.
        $resumen = [
            'compras' => Purchase::count(),
            'gasto_mes' => Purchase::whereBetween('purchase_date', [
                now()->startOfMonth()->format('Y-m-d'),
                now()->endOfMonth()->format('Y-m-d'),
            ])->sum('total_amount'),
            'ultima' => Purchase::orderByDesc('purchase_date')->value('purchase_date'),
        ];

        return view('produccion.compras', compact(
            'compras',
            'insumos',
            'leche',
            'resumen'
        ));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $this->validarCompra($request);

        try {
            $compra = $this->compras->registrar($datos, Auth::user());
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Compra registrada: S/ {$compra->total_amount} a {$compra->supplier->name}.");
    }

    public function update(Request $request, Purchase $compra)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $this->validarCompra($request);

        try {
            $compra = $this->compras->actualizar($compra, $datos, Auth::user());
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Compra corregida: queda en S/ {$compra->total_amount}.");
    }

    public function destroy(Purchase $compra)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $etiqueta = $compra->label();

        try {
            $this->compras->eliminar($compra, Auth::user());
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back()->with('success', "Se anuló la {$etiqueta} y se devolvió su mercadería al almacén.");
    }

    /** @return array<string, mixed> */
    private function validarCompra(Request $request): array
    {
        return $request->validate([
            // La compra se sube como quien copia una boleta: se escribe a quién
            // figura en ella. El servicio reutiliza la ficha que ya exista
            // -por RUC primero, por nombre después- antes de crear otra.
            'new_supplier_name' => ['required', 'string', 'max:120'],
            'new_supplier_document' => ['nullable', 'string', 'max:20'],
            'purchase_date' => ['required', 'date'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.supply_id' => ['required', 'exists:supplies,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function bloquearSiNoAutorizado()
    {
        if (! in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true)) {
            return redirect()->route('dashboard')
                ->withErrors(['general' => 'Solo la jefatura de producción registra las compras de la planta.']);
        }

        return null;
    }
}
