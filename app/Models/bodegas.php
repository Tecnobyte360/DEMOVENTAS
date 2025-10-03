<?php

namespace App\Models;

use App\Models\Productos\Producto;
use Illuminate\Database\Eloquent\Model;

class bodegas extends Model
{
    
    protected $table = 'bodegas';

    protected $fillable = ['nombre', 'ubicacion', 'activo'];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_bodega')
                    ->withPivot('stock', 'stock_minimo', 'stock_maximo')
                    ->withTimestamps(); // 👈 También aquí
    }
    
}
