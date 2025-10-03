<?php

namespace App\Models\Productos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoBodega extends Model
{
    use HasFactory;

    protected $table = 'producto_bodega';

    protected $fillable = [
        'producto_id',
        'bodega_id',
        'stock',
        'stock_minimo',
        'stock_maximo',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega()
    {
        return $this->belongsTo(\App\Models\bodegas::class);
    }
    public function scopeFila($query, int $productoId, int $bodegaId)
{
    return $query->where('producto_id', $productoId)->where('bodega_id', $bodegaId);
}
}
