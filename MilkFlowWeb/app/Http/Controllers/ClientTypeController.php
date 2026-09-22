<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Services\Sistema\SistemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tipos de cliente: a quién se le cobra qué.
 *
 * Aquí se define la tarifa estándar de cada tipo —lo que se cobra por unidad
 * de cualquier producto— y, si corresponde, el rol del padrón que cae solo en
 * ese tipo. La tarifa fina de un producto concreto se pone en Productos y
 * Recetas, y manda sobre este estándar.
 */
class ClientTypeController extends Controller
{
    public function __construct(private SistemaService $sistema) {}

    public function index(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $tipos = ClientType::with('roles')->withCount(['customers', 'productPrices'])
            ->when($request->filled('buscar'), fn ($q) => $q->where('name', 'like', '%'.$request->buscar.'%'))
            ->when($request->filled('estado'), fn ($q) => $q->where('is_active', $request->estado === 'activos'))
            ->orderBy('min_quantity')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roles = config('huata.roles');

        return view('admin.tipos-cliente.index', compact('tipos', 'roles'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        try {
            $tipo = $this->sistema->crearTipoCliente($this->validar($request));
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Tipo de cliente #{$tipo->id} guardado: {$tipo->name} · desde {$tipo->min_quantity} unidad(es).");
    }

    public function update(Request $request, ClientType $tipo)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $datos = $this->validar($request);
        $datos['is_active'] = $request->boolean('is_active');

        try {
            $tipo = $this->sistema->actualizarTipoCliente($tipo, $datos);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Tipo de cliente #{$tipo->id} guardado: {$tipo->name} · desde {$tipo->min_quantity} unidad(es).");
    }

    public function destroy(ClientType $tipo)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $nombre = $tipo->name;

        try {
            $this->sistema->eliminarTipoCliente($tipo);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back()->with('success', "Tipo de cliente «{$nombre}» eliminado.");
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'min_quantity' => ['nullable', 'numeric', 'min:0'],
            'auto_roles' => ['nullable', 'array'],
            'auto_roles.*' => ['string', 'in:'.implode(',', array_keys(config('huata.roles')))],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
