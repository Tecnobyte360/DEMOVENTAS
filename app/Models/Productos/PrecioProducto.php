<?php

namespace App\Models\Productos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrecioProducto extends Model
{
    use HasFactory;

    protected $table = 'precios_producto';

    protected $fillable = [
        'producto_id',
        'nombre',
        'valor',
    ];

 
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
