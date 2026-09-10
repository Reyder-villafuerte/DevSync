<?php

namespace App\Models;

use App\Support\Concerns\UsaUuid;
use Illuminate\Database\Eloquent\Model;

// No sincronizable: vive solo en el servidor.
class SyncLog extends Model
{
    use UsaUuid;

    protected $table = 'sync_logs';

    protected $fillable = [
        'dispositivo_id', 'usuario_id', 'direccion', 'entidad',
        'recibidos', 'aceptados', 'conflictos',
        'cursor_desde', 'cursor_hasta', 'ejecutado_en',
    ];

    protected function casts(): array
    {
        return ['ejecutado_en' => 'datetime'];
    }
}
