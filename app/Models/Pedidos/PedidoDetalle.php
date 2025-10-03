<?php

namespace App\Models\Pedidos;

use App\Models\bodegas;
use App\Models\Productos\PrecioProducto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'bodega_id',
        'cantidad',

        // Nuevos campos agregados en la migración:
        'precio_unitario',
        'precio_lista_id',
         'estado',
    ];

    protected $casts = [
        // Asegura siempre dos decimales al leer/escribir este campo
        'precio_unitario' => 'decimal:2',
    ];

    // -------------------------
    // Relaciones
    // -------------------------

    /**
     * El pedido al que pertenece este detalle.
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * El producto asociado a este detalle.
     */
    public function producto()
    {
        return $this->belongsTo(\App\Models\Productos\Producto::class);
    }

    /**
     * La bodega asociada a este detalle.
     */
    public function bodega()
    {
        return $this->belongsTo(bodegas::class);
    }

    /**
     * La lista de precios (PrecioProducto) que se aplicó, si corresponde.
     */
    public function precioLista()
    {
        return $this->belongsTo(PrecioProducto::class, 'precio_lista_id');
    }

    
}
