<?php

namespace App\Models\Inventario;

use App\Models\Bodega;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Productos\Producto;


class SalidaMercanciaDetalle extends Model
{
    use HasFactory;

    protected $table = 'salida_mercancia_detalles';

    protected $fillable = [
        'salida_mercancia_id',
        'producto_id',
        'bodega_id',
        'cantidad',
    ];

    // Relaciones

    public function salida()
    {
        return $this->belongsTo(SalidaMercancia::class, 'salida_mercancia_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }
}
