<?php

namespace App\Models\Productos;

use Illuminate\Database\Eloquent\Model;

class ProductoCuentaTipo extends Model
{
    protected $table = 'producto_cuenta_tipos';

    protected $fillable = ['codigo','nombre','obligatorio','activo','orden'];

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }
}
