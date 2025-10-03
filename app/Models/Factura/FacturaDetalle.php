<?php

namespace App\Models\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaDetalle extends Model
{
    protected $table = 'factura_detalles';

    protected $fillable = [
        'factura_id','producto_id','bodega_id','descripcion',
        'cantidad','precio_unitario','descuento_pct','impuesto_pct',
        'importe_base','importe_impuesto','importe_total','cuenta_ingreso_id'
    ];

    protected $casts = [
        'cantidad'         => 'decimal:3',
        'precio_unitario'  => 'decimal:2',
        'descuento_pct'    => 'decimal:3',
        'impuesto_pct'     => 'decimal:3',
        'importe_base'     => 'decimal:2',
        'importe_impuesto' => 'decimal:2',
        'importe_total'    => 'decimal:2',
        'cuenta_ingreso_id'=> 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Factura\factura::class, 'factura_id');
    }

    // ⬇️ RELACIONES QUE FALTABAN
    public function producto(): BelongsTo
    {
        // ajusta la clase si tu modelo está en otro namespace
        return $this->belongsTo(\App\Models\Productos\Producto::class, 'producto_id')->withDefault();
    }

    public function bodega(): BelongsTo
    {
        // según tu código usas App\Models\bodegas
        return $this->belongsTo(\App\Models\bodegas::class, 'bodega_id')->withDefault();
    }

    // Cálculo de importes
    public function calcularImportes(): void
    {
        $cant = (float) ($this->cantidad ?? 0);
        $prec = (float) ($this->precio_unitario ?? 0);
        $desc = (float) ($this->descuento_pct ?? 0);
        $iva  = (float) ($this->impuesto_pct ?? 0);

        $base = ($cant * $prec) * (1 - ($desc / 100));
        $imp  = $base * ($iva / 100);
        $tot  = $base + $imp;

        $this->importe_base     = round($base, 2);
        $this->importe_impuesto = round($imp, 2);
        $this->importe_total    = round($tot, 2);
    }

    protected static function booted(): void
    {
        static::saving(fn (self $m) => $m->calcularImportes());
    }
}
