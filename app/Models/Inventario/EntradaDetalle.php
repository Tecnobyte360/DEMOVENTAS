<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bodega;
use App\Models\Productos\Producto;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Conceptos\ConceptoDocumento;

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
        'descripcion',
        'cuenta_id',
        'cuenta_str',
    ];

    protected $casts = [
        'cantidad'        => 'float',
        'precio_unitario' => 'float',
    ];

    public function entrada()
    {
        return $this->belongsTo(EntradaMercancia::class, 'entrada_mercancia_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function concepto()
    {
        return $this->belongsTo(ConceptoDocumento::class, 'concepto_documento_id');
    }
}
