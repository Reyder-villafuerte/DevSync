<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditoria';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['datos_anteriores' => 'array', 'datos_nuevos' => 'array'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
