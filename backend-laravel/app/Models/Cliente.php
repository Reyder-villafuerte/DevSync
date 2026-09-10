<?php

namespace App\Models;

use App\Enums\TipoCliente;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use Sincronizable;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre', 'tipo_cliente', 'documento_identidad', 'direccion', 'telefono', 'activo',
    ];

    protected function casts(): array
    {
        return ['tipo_cliente' => TipoCliente::class, 'activo' => 'boolean'];
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
