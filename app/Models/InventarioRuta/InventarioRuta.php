<?php

namespace App\Models\InventarioRuta;

use App\Models\bodegas;
use App\Models\Productos\Producto;
use Illuminate\Database\Eloquent\Model;

class InventarioRuta extends Model
{
 protected $table = 'inventario_ruta';

protected $fillable = ['ruta_id', 'producto_id', 'bodega_id', 'cantidad', 'cantidad_inicial','cantidad_devuelta'];
    

    public function ruta()
    {
        return $this->belongsTo(\App\Models\Ruta\Ruta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega()
    {
        return $this->belongsTo(bodegas::class);
    }
}
