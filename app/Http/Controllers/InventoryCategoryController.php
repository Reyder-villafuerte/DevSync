<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\InventoryCategory;
use App\Models\MeasurementUnit;
use App\Services\Produccion\CatalogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Categorías y unidades de medida: el vocabulario con el que la planta ordena
 * su almacén antes de dar de alta cualquier insumo.
 */
class InventoryCategoryController extends Controller
{
    public function __construct(private CatalogoService $catalogo) {}

    public function index(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $categorias = InventoryCategory::withCount(['supplies', 'products'])
            ->when($request->filled('buscar'), fn ($q) => $q->where('name', 'like', '%'.$request->buscar.'%'))
            ->when($request->filled('estado'), fn ($q) => $q->where('is_active', $request->estado === 'activas'))
            ->orderBy('name')
            ->paginate(10, ['*'], 'pagina_categorias')
            ->withQueryString();

        $unidades = MeasurementUnit::withCount(['supplies', 'products'])
            ->when($request->filled('buscar_unidad'), fn ($q) => $q->where('name', 'like', '%'.$request->buscar_unidad.'%'))
            ->orderBy('name')
            ->paginate(10, ['*'], 'pagina_unidades')
            ->withQueryString();

        return view('produccion.categorias', compact('categorias', 'unidades'));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $categoria = $this->catalogo->crearCategoria(
                $datos['name'],
                $datos['description'] ?? null,
                Auth::user()
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Categoría «{$categoria->name}» creada.");
    }

    public function storeUnidad(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
        ]);

        try {
            $unidad = $this->catalogo->crearUnidadMedida($datos['name'], $datos['abbreviation']);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Unidad de medida {$unidad->label()} creada.");
    }

    public function update(Request $request, InventoryCategory $categoria)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $this->catalogo->actualizarCategoria(
                $categoria,
                $datos['name'],
                $datos['description'] ?? null,
                $request->boolean('is_active')
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back()->with('success', "Categoría «{$categoria->name}» actualizada.");
    }

    public function destroy(InventoryCategory $categoria)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $nombre = $categoria->name;

        try {
            $this->catalogo->eliminarCategoria($categoria);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back()->with('success', "Categoría «{$nombre}» eliminada.");
    }

    public function updateUnidad(Request $request, MeasurementUnit $unidad)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
        ]);

        try {
            $this->catalogo->actualizarUnidadMedida($unidad, $datos['name'], $datos['abbreviation']);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back()->with('success', "Unidad {$unidad->label()} actualizada.");
    }

    public function destroyUnidad(MeasurementUnit $unidad)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $etiqueta = $unidad->label();

        try {
            $this->catalogo->eliminarUnidadMedida($unidad);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back()->with('success', "Unidad {$etiqueta} eliminada.");
    }

    private function bloquearSiNoAutorizado()
    {
        if (! in_array(Auth::user()->role, CatalogoAlmacenController::ROLES_PERMITIDOS, true)) {
            return redirect()->route('dashboard')
                ->withErrors(['general' => 'Solo la jefatura de producción administra el catálogo de la planta.']);
        }

        return null;
    }
}
