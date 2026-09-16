<?php

namespace App\Http\Controllers;

use App\Services\BusinessWeek;
use App\Services\Modules;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        $allowed = match ($r->user()->roles->first()->slug) {
            'admin' => ['entregas', 'producers', 'acopiadores', 'calidad', 'lotes', 'ventas', 'stock', 'liquidaciones'],'supervisor' => ['calidad', 'entregas'],'produccion' => ['lotes'],'despacho' => ['ventas', 'stock'],default => []
        };
        $data = $r->validate(['modulo' => ['nullable', Rule::in($allowed)], 'periodo' => 'nullable|in:diario,semanal,mensual,personalizado', 'desde' => 'nullable|date', 'hasta' => 'nullable|date|after_or_equal:desde', 'productor_id' => 'nullable|integer|exists:productores,id', 'acopiador_id' => 'nullable|integer|exists:acopiadores,id', 'zona_id' => 'nullable|integer|exists:zonas,id', 'sector_id' => 'nullable|integer|exists:sectores,id', 'ruta_id' => 'nullable|integer|exists:rutas,id', 'estado' => 'nullable|string|max:30', 'calidad_filtro' => 'nullable|in:adulteracion,acidez', 'export' => 'nullable|in:pdf,csv']);
        $module = $data['modulo'] ?? $allowed[0];
        $definition = Modules::get($module);
        $period = $data['periodo'] ?? 'semanal';
        [$from,$to] = match ($period) {
            'diario' => [today(), today()->endOfDay()],'mensual' => [today()->startOfMonth(), today()->endOfMonth()],'personalizado' => [Carbon::parse($data['desde'] ?? today())->startOfDay(), Carbon::parse($data['hasta'] ?? today())->endOfDay()],default => BusinessWeek::bounds()
        };
        $query = $definition['model']::query();
        if (in_array($module, ['entregas', 'calidad', 'lotes', 'ventas', 'liquidaciones'])) {
            $query->whereBetween(match ($module) {
                'entregas','calidad' => 'fecha_hora','liquidaciones' => 'periodo_inicio',default => 'fecha'
            }, [$from, $to]);
        }
        $deliveryFilters = function ($q) use ($data) {
            foreach (['productor_id', 'acopiador_id', 'zona_id', 'sector_id', 'ruta_id'] as $field) {
                if (! empty($data[$field])) {
                    $q->where($field, $data[$field]);
                }
            }
        };
        if ($module === 'entregas') {
            $deliveryFilters($query);
        }
        if ($module === 'calidad') {
            $query->whereHas('entrega', $deliveryFilters);
            if (($data['calidad_filtro'] ?? '') === 'adulteracion') {
                $query->where('agua_agregada_porcentaje', '>', 0);
            } if (($data['calidad_filtro'] ?? '') === 'acidez') {
                $query->where('ph', '<', 6.5);
            }
        }
        if ($module === 'producers') {
            foreach (['zona_id', 'sector_id'] as $field) {
                if (! empty($data[$field])) {
                    $query->where($field, $data[$field]);
                }
            } if (! empty($data['productor_id'])) {
                $query->where('id', $data['productor_id']);
            }
        }
        if ($module === 'acopiadores' && ! empty($data['acopiador_id'])) {
            $query->where('id', $data['acopiador_id']);
        }
        if ($module === 'liquidaciones' && ! empty($data['productor_id'])) {
            $query->where('productor_id', $data['productor_id']);
        }
        if (! empty($data['estado']) && in_array($module, ['entregas', 'calidad', 'lotes', 'ventas', 'liquidaciones'])) {
            $query->where($module === 'calidad' ? 'resultado' : 'estado', $data['estado']);
        }
        $totals = ['Registros' => (clone $query)->count()];
        foreach (match ($module) {
            'entregas' => ['litros' => 'Litros'],'lotes' => ['litros_leche' => 'Litros procesados', 'moldes_obtenidos' => 'Unidades obtenidas'],'ventas' => ['cantidad' => 'Quesos vendidos', 'total' => 'Ventas S/'],'stock' => ['cantidad' => 'Stock actual'],'liquidaciones' => ['litros' => 'Litros', 'total' => 'Liquidaciones S/'],default => []
        } as $field => $label) {
            $totals[$label] = (clone $query)->sum($field);
        }
        if ($module === 'lotes') {
            $totals['Rendimiento (%)'] = round(($totals['Unidades obtenidas'] ?? 0) / max(1, $totals['Litros procesados'] ?? 0) * 100, 2);
        }
        $rows = $r->filled('export') ? $query->orderBy('id')->get() : $query->orderByDesc('id')->paginate(30)->withQueryString();
        $viewData = compact('definition', 'module', 'rows', 'totals', 'from', 'to', 'allowed', 'period');
        if ($r->input('export') === 'pdf') {
            return Pdf::loadView('reports.pdf', $viewData)->setPaper('a4', 'landscape')->download('milkflow-'.$module.'.pdf');
        }
        if ($r->input('export') === 'csv') {
            return response()->streamDownload(function () use ($definition, $rows) {
                $f = fopen('php://output', 'w');
                fwrite($f, "\xEF\xBB\xBF");
                fputcsv($f, array_values($definition['columns']));
                foreach ($rows as $row) {
                    fputcsv($f, array_map(function ($key) use ($row) {
                        $v = (string) $row->$key;

                        return preg_match('/^[=+@-]/', $v) ? "'".$v : $v;
                    }, array_keys($definition['columns'])));
                } fclose($f);
            }, 'milkflow-'.$module.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('reports.index',$viewData);
    }
}
