<?php

namespace App\Models;

use App\Models\Productos\Producto;
use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    
    protected $table = 'bodegas';

    protected $fillable = ['nombre', 'ubicacion', 'activo'];

 public function productos()
{
    return $this->belongsToMany(\App\Models\Productos\Producto::class, 'producto_bodega')
        ->withPivot('stock', 'stock_minimo', 'stock_maximo', 'costo_promedio', 'ultimo_costo', 'metodo_costeo')
        ->withTimestamps();
}

    
}
