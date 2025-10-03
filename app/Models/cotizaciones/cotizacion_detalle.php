<?php

namespace App\Models\cotizaciones;

use App\Livewire\Cotizaciones\Cotizacion;
use App\Models\bodegas;
use App\Models\Productos\PrecioProducto;
use App\Models\Productos\Producto;
use Illuminate\Database\Eloquent\Model;

class cotizacion_detalle extends Model
{
   protected $table = 'cotizacion_detalles';

    protected $fillable = [
        'cotizacion_id', 'producto_id', 'bodega_id', 'cantidad',
        'precio_unitario', 'precio_lista_id', 'descuento_pct', 'impuesto_pct', 'importe',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:3',
        'precio_unitario' => 'decimal:2',
        'descuento_pct'   => 'decimal:3',
        'impuesto_pct'    => 'decimal:3',
        'importe'         => 'decimal:2',
    ];

  public function cotizacion()
{
    return $this->belongsTo(cotizacione::class, 'cotizacion_id');
}

    public function producto()   { return $this->belongsTo(Producto::class); }
    public function bodega()     { return $this->belongsTo(bodegas::class, 'bodega_id'); }
    public function precioLista(){ return $this->belongsTo(PrecioProducto::class, 'precio_lista_id'); }

    public function recalcularImporte(): void {
        $base = ($this->cantidad * $this->precio_unitario) * (1 - $this->descuento_pct/100);
        $this->importe = round($base, 2);
        $this->save();
    }
}
