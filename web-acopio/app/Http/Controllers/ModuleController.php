<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModuleRequest;
use App\Models as M;
use App\Notifications\MilkFlowNotification;
use App\Services\Audit;
use App\Services\DeliveryService;
use App\Services\Modules;
use App\Services\ProductionService;
use App\Services\QualityService;
use App\Services\RotationService;
use App\Services\SalesService;
use App\Services\Settings;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ModuleController extends Controller
{
    public function query(string $module)
    {
        $q = Modules::get($module)['model']::query();
        $u = auth()->user();
        if ($u->hasRole('productor')) {
            if (in_array($module, ['entregas', 'liquidaciones', 'rotaciones'])) {
                $q->where('productor_id', $u->productor?->id ?? 0);
            } elseif ($module === 'calidad') {
                $q->whereHas('entrega', fn ($e) => $e->where('productor_id', $u->productor?->id ?? 0));
            } elseif ($module === 'comunicados') {
                $q->where('activo', true)->whereDate('fecha', '<=', today());
            } else {
                abort(403);
            }
        }
        if ($u->hasRole('acopiador')) {
            if ($module === 'entregas') {
                $q->where('acopiador_id', $u->acopiador?->id ?? 0);
            }
            if ($module === 'producers') {
                $q->whereIn('sector_id', DB::table('ruta_sectores')->join('rutas', 'ruta_sectores.ruta_id', '=', 'rutas.id')->where('rutas.acopiador_id', $u->acopiador?->id ?? 0)->where('rutas.activo', true)->select('sector_id'));
            }
        }

        return $q;
    }

    public function index(Request $r, string $module)
    {
        $definition = Modules::get($module);
        $q = $this->query($module);
        if ($r->boolean('hoy')) {
            $q->whereDate($module === 'calidad' ? 'fecha_hora' : 'fecha', today());
        }
        $rows = $q->orderByDesc('id')->paginate(20)->withQueryString();
        $base = '/'.$r->segment(1).'/'.$module;
        $canCreate = auth()->user()->hasPermission($definition['permission']) && count($definition['fields']) && $module !== 'capacitaciones';

        return view('modules.index', compact('definition', 'module', 'rows', 'base', 'canCreate'));
    }

    public function show(Request $r)
    {
        $module = $r->route('module');
        $definition = Modules::get($module);
        $row = $this->query($module)->findOrFail($r->route('id'));
        $base = '/'.$r->segment(1).'/'.$module;
        if ($module === 'entregas') {
            Gate::authorize('view', $row);
        }
        $history = match ($module) {
            'producers' => $row->entregas()->latest()->limit(100)->get(['uuid', 'litros', 'fecha_hora', 'estado'])->map->toArray(),
            'entregas' => $row->problemas()->get(['tipo', 'descripcion', 'severidad', 'estado'])->map->toArray(),
            'liquidaciones' => $row->detalles()->get(['entrega_id', 'litros', 'tarifa', 'subtotal'])->map->toArray(),
            'ventas' => $row->detalles()->get(['stock_queso_id', 'cantidad', 'precio_unitario', 'subtotal'])->map->toArray(),
            'lotes' => $row->entregas()->get()->map(fn ($e) => ['entrega' => $e->uuid, 'litros' => $e->pivot->litros]),
            default => collect(),
        };

        return view('modules.detail', compact('module', 'definition', 'row', 'base', 'history'));
    }

    public function form(Request $r)
    {
        $module = $r->route('module');
        $id = $r->route('id');
        $definition = Modules::get($module);
        abort_unless(auth()->user()->hasPermission($definition['permission']), 403);
        abort_if(empty($definition['fields']), 403);
        if ($id) {
            abort_unless(Modules::editable($module), 403);
        }
        abort_if(! $id && $module === 'capacitaciones', 403);
        $row = $id ? $this->query($module)->findOrFail($id) : null;
        $base = '/'.$r->segment(1).'/'.$module;

        return view('modules.form', compact('definition', 'module', 'row', 'base'));
    }

    public function save(ModuleRequest $r)
    {
        $module = $r->route('module');
        $id = $r->route('id');
        $data = $r->validated();
        $actor = $r->user();
        if ($id) {
            abort_unless(Modules::editable($module), 403);
        }
        abort_if(empty(Modules::get($module)['fields']) || (! $id && $module === 'capacitaciones'), 403);
        if (! $id) {
            $service = match ($module) {
                'entregas' => DeliveryService::class,'calidad' => QualityService::class,'lotes' => ProductionService::class,'ventas' => SalesService::class,default => null
            };
            if ($service) {
                app($service)->create($data, $actor);
            } elseif ($module === 'liquidaciones') {
                app(SettlementService::class)->calculate($data['productor_id'], $data['periodo'], $actor);
            } elseif ($module === 'rotaciones') {
                app(RotationService::class)->request($data);
            } else {
                $this->catalog($module, $data, null);
            }
        } else {
            $this->catalog($module, $data, $id);
        }
        $response = redirect('/'.$r->segment(1).'/'.$module)->with('status', 'Registro guardado correctamente.');
        if ($module === 'entregas' && (now()->format('H:i') < Settings::get('hora_inicio', '04:30') || now()->format('H:i') > Settings::get('hora_fin', '12:00'))) {
            $response->with('warning', 'Advertencia: entrega registrada fuera del horario operativo.');
        }

        return $response;
    }

    private function catalog(string $module, array $data, ?int $id): void
    {
        DB::transaction(function () use ($module, $data, $id) {
            $class = Modules::get($module)['model'];
            $row = $id ? $class::lockForUpdate()->findOrFail($id) : new $class;
            $before = $row->getAttributes();
            if ($module === 'producers') {
                $sector = M\Sector::with('zona.distrito')->findOrFail($data['sector_id']);
                if (! $sector->activo || ! $sector->zona->activo || $sector->zona->distrito->nombre !== 'HUARI') {
                    throw ValidationException::withMessages(['sector_id' => 'Selecciona un sector activo del distrito de Huari.']);
                }
                if ($id && ($row->zona_id != $sector->zona_id || $row->sector_id != $sector->id)) {
                    throw ValidationException::withMessages(['sector_id' => 'Para cambiar sector o zona utiliza Rotación estacional.']);
                }
                if ($row->expulsado_at && $data['activo']) {
                    throw ValidationException::withMessages(['activo' => 'Un productor expulsado definitivamente no puede reactivarse.']);
                }
                if (! empty($data['user_id']) && ! M\User::findOrFail($data['user_id'])->hasRole('productor')) {
                    abort(422, 'La cuenta debe tener el rol Productor.');
                }
                $data['zona_id'] = $sector->zona_id;
                $data['distrito_id'] = $sector->zona->distrito_id;
            }
            if ($module === 'zones') {
                $data['distrito_id'] = M\Distrito::where('nombre', 'HUARI')->firstOrFail()->id;
            }
            if ($module === 'sectors' && $id && $row->zona_id != $data['zona_id'] && M\Productor::where('sector_id', $id)->exists()) {
                throw ValidationException::withMessages(['zona_id' => 'No se puede mover un sector con productores; utiliza rotación.']);
            }
            if ($module === 'acopiadores' && ! M\User::findOrFail($data['user_id'])->hasRole('acopiador')) {
                abort(422, 'La cuenta debe tener el rol Acopiador.');
            }
            $sectors = $data['sectores'] ?? null;
            unset($data['sectores']);
            if ($module === 'routes' && ! M\Acopiador::findOrFail($data['acopiador_id'])->activo) {
                abort(422, 'Acopiador inactivo.');
            }
            if ($module === 'problemas' && ! $id) {
                $data += ['fecha' => now(), 'usuario_id' => auth()->id()];
            }
            if ($module === 'comunicados') {
                $data['autor'] = auth()->id();
            }
            $row->fill($data);
            $row->save();
            if ($sectors) {
                $row->sectores()->sync($sectors);
            } Audit::record($module.($id ? '.actualizado' : '.creado'), $row, $before);
            if ($module === 'comunicados' && $row->activo && $row->fecha <= today()->toDateString()) {
                Notification::send(M\User::where('status', 'ACTIVO')->get(), new MilkFlowNotification($row->titulo, $row->mensaje));
            }
        });
    }

    public function pay(int $id)
    {
        DB::transaction(function () use ($id) {
            $row = M\Liquidacion::lockForUpdate()->findOrFail($id);
            abort_unless($row->estado === 'Calculada', 422);
            $before = $row->getAttributes();
            $row->update(['estado' => 'Pagada', 'pagada_at' => now()]);
            Audit::record('liquidacion.pagada', $row, $before);
            $row->productor->user?->notify(new MilkFlowNotification('Liquidación pagada', 'Pago registrado: S/ '.$row->total));
        });

        return back()->with('status', 'Pago registrado.');
    }

    public function rotate(Request $r, int $id)
    {
        $data = $r->validate(['decision' => 'required|in:APROBADA,RECHAZADA', 'motivo' => 'required_if:decision,RECHAZADA|nullable|string|max:500']);
        app(RotationService::class)->review($id,$data['decision'],$data['motivo'] ?? '');

        return back()->with('status','Rotación revisada.');
    }
}
