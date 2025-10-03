<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaCreditoDetalle extends Model
{
    protected $table = 'nota_credito_detalles';
    protected $guarded = [];

    public function nota()       { return $this->belongsTo(NotaCredito::class, 'nota_credito_id'); }
    public function producto()   { return $this->belongsTo(\App\Models\Productos\Producto::class); }
    public function cuenta()     { return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_ingreso_id'); }
    public function bodega()     { return $this->belongsTo(\App\Models\bodegas::class, 'bodega_id'); }
    public function impuesto()   { return $this->belongsTo(\App\Models\Impuestos\Impuesto::class, 'impuesto_id'); }
}
