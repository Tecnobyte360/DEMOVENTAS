<?php

namespace App\Models\Devoluciones;

use App\Models\bodegas;
use App\Models\Productos\Producto;
use App\Models\Productos\PrecioProducto;
use Illuminate\Database\Eloquent\Model;

class DevolucionDetalle extends Model
{
    protected $table = 'devolucion_detalles';

    protected $fillable = [
        'devolucion_id',
        'producto_id',
        'bodega_id',
        'cantidad',

        // NUEVOS CAMPOS:
        'precio_unitario',
        'precio_lista_id',
    ];

    // Relaciones

    public function devolucion()
    {
        return $this->belongsTo(Devolucion::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega()
    {
        return $this->belongsTo(bodegas::class);
    }

    /**
     * Relación con la lista de precios (si la devolución incluyó esa info).
     * DevolucionDetalle.precio_lista_id → PrecioProducto.id
     */
    public function precioLista()
    {
        return $this->belongsTo(PrecioProducto::class, 'precio_lista_id');
    }
}
