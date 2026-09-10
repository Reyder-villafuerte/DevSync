<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisoVisto extends Model
{
    use Sincronizable;

    protected $table = 'avisos_vistos';

    protected $fillable = ['aviso_id', 'productor_id', 'visto_en'];

    protected function casts(): array
    {
        return ['visto_en' => 'datetime'];
    }

    public function aviso(): BelongsTo
    {
        return $this->belongsTo(Aviso::class, 'aviso_id');
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }
}
