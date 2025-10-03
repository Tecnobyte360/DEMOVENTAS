<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Productos\Producto;
use App\Models\bodegas;

class EntradaDetalle extends Model
{
    use HasFactory;

    protected $table = 'entrada_detalles';

    protected $fillable = [
        'entrada_mercancia_id',
        'producto_id',
        'bodega_id',
        'cantidad',
        'precio_unitario',
    ];

    public function entrada()
    {
        return $this->belongsTo(EntradaMercancia::class, 'entrada_mercancia_id');
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
