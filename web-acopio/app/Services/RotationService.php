<?php

namespace App\Services;

use App\Models\Productor;
use App\Models\RotacionProductor;
use App\Models\Sector;
use Illuminate\Support\Facades\DB;

class RotationService
{
    public function request(array $data): RotacionProductor
    {
        return DB::transaction(function () use ($data) {
            $p = Productor::lockForUpdate()->findOrFail($data['productor_id']);
            abort_unless(auth()->user()->hasRole('admin') || $p->user_id === auth()->id(), 403);
            $sector = Sector::findOrFail($data['sector_nuevo_id']);
            abort_unless($sector->activo && $sector->zona->activo, 422, 'El sector no está activo.');
            abort_if($p->rotaciones()->whereIn('estado', ['PENDIENTE', 'APROBADA'])->whereNull('aplicada_at')->exists(), 422, 'Ya existe una rotación pendiente.');
            $r = RotacionProductor::create($data + ['zona_anterior_id' => $p->zona_id, 'sector_anterior_id' => $p->sector_id, 'zona_nueva_id' => $sector->zona_id, 'solicitado_por' => auth()->id()]);
            Audit::record('rotacion.solicitada', $r);

            return $r;
        });
    }

    public function review(int $id, string $decision, string $reason = ''): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        DB::transaction(function () use ($id, $decision, $reason) {
            $r = RotacionProductor::lockForUpdate()->findOrFail($id);
            abort_unless($r->estado === 'PENDIENTE', 422, 'Solicitud ya revisada.');
            $before = $r->getAttributes();
            $r->update(['estado' => $decision, 'revisado_por' => auth()->id(), 'motivo' => $reason]);
            Audit::record('rotacion.revisada', $r, $before);
        });
        $this->applyDue();
    }

    public function applyDue(): void
    {
        foreach (RotacionProductor::where('estado', 'APROBADA')->whereNull('aplicada_at')->whereDate('fecha_efectiva', '<=', today())->pluck('id') as $id) {
            DB::transaction(function () use ($id) {
                $r = RotacionProductor::lockForUpdate()->findOrFail($id);
                if ($r->aplicada_at) {
                    return;
                } $p = Productor::lockForUpdate()->findOrFail($r->productor_id);
                $before = $p->getAttributes();
                $p->update(['zona_id' => $r->zona_nueva_id, 'sector_id' => $r->sector_nuevo_id]);
                $r->update(['aplicada_at' => now()]);
                Audit::record('productor.rotacion_aplicada',$p,$before);
            });
        }
    }
}
