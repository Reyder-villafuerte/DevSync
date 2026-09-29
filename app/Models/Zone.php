<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_active'];

    public function producers()
    {
        return $this->hasMany(User::class)->where('role', 'productor');
    }

    public function routes()
    {
        return $this->hasMany(CollectionRoute::class);
    }
}
